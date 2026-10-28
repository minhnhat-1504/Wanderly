<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Trip;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SocialFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSocialTest extends TestCase
{
    use RefreshDatabase;

    protected SocialFeedService $feedService;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->feedService = app(SocialFeedService::class);
        $this->notificationService = app(NotificationService::class);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOCIAL FEED — POST CRUD
    // ══════════════════════════════════════════════════════════════════════

    public function test_guest_cannot_access_social_feed(): void
    {
        $this->get(route('social.feed'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_social_feed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('social.feed'))
            ->assertOk()
            ->assertViewIs('social.feed');
    }

    public function test_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('social.posts.store'), [
                'title' => 'My Amazing Dalat Trip',
                'content' => 'We visited the flower gardens and coffee farms. Highly recommend!',
            ])
            ->assertRedirect(route('social.feed'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'My Amazing Dalat Trip',
        ]);
    }

    public function test_post_creation_requires_title_and_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('social.posts.store'), [])
            ->assertSessionHasErrors(['title', 'content']);
    }

    public function test_user_can_create_post_with_trip_attached(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $user->id]);

        $this->actingAs($user)
            ->post(route('social.posts.store'), [
                'title' => 'Sharing my trip plan!',
                'content' => 'Check out my itinerary for Hanoi.',
                'trip_id' => $trip->id,
            ])
            ->assertRedirect(route('social.feed'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'trip_id' => $trip->id,
        ]);
    }

    public function test_author_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('social.posts.destroy', $post))
            ->assertRedirect(route('social.feed'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_non_author_cannot_delete_another_users_post(): void
    {
        $author = User::factory()->create();
        $intruder = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($intruder)
            ->delete(route('social.posts.destroy', $post))
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOCIAL FEED — LIKES
    // ══════════════════════════════════════════════════════════════════════

    public function test_user_can_like_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id, 'likes_count' => 0]);

        $response = $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/like');

        $response->assertOk()
            ->assertJson(['liked' => true]);

        $this->assertDatabaseHas('likes', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertEquals(1, $post->fresh()->likes_count);
    }

    public function test_user_can_unlike_a_previously_liked_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id, 'likes_count' => 1]);
        $post->likedByUsers()->attach($user->id);

        $response = $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/like');

        $response->assertOk()
            ->assertJson(['liked' => false]);

        $this->assertDatabaseMissing('likes', ['post_id' => $post->id, 'user_id' => $user->id]);
        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOCIAL FEED — COMMENTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_user_can_comment_on_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id, 'comments_count' => 0]);

        $response = $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/comments', [
                'content' => 'Great post! I visited there last month.',
            ]);

        $response->assertOk()
            ->assertJsonPath('comment.author', $user->name);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Great post! I visited there last month.',
        ]);
        $this->assertEquals(1, $post->fresh()->comments_count);
    }

    public function test_comment_requires_content(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/comments', ['content' => ''])
            ->assertUnprocessable();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOCIAL FEED — SAVE / BOOKMARK
    // ══════════════════════════════════════════════════════════════════════

    public function test_user_can_save_and_unsave_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        // Save
        $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/save')
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('post_saves', ['post_id' => $post->id, 'user_id' => $user->id]);

        // Unsave
        $this->actingAs($user)
            ->postJson('/ajax/social/posts/'.$post->id.'/save')
            ->assertOk()
            ->assertJson(['saved' => false]);

        $this->assertDatabaseMissing('post_saves', ['post_id' => $post->id, 'user_id' => $user->id]);

        // Also test direct web route /social/posts/{post}/save
        $this->actingAs($user)
            ->postJson(route('social.posts.save', $post))
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('post_saves', ['post_id' => $post->id, 'user_id' => $user->id]);
    }

    public function test_user_can_comment_via_web_route(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($user)
            ->postJson(route('social.posts.comments.store', $post), [
                'content' => 'Commenting via direct web route!',
            ]);

        $response->assertOk()
            ->assertJsonPath('comment.author', $user->name);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Commenting via direct web route!',
        ]);
    }

    public function test_saved_tab_shows_only_saved_posts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $savedPost = Post::factory()->create(['user_id' => $other->id]);
        $unsavedPost = Post::factory()->create(['user_id' => $other->id]);
        $savedPost->savedByUsers()->attach($user->id);

        $this->actingAs($user)
            ->get(route('social.feed', ['tab' => 'saved']))
            ->assertOk()
            ->assertSee($savedPost->title)
            ->assertDontSee($unsavedPost->title);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SOCIAL FEED SERVICE — UNIT TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_feed_service_toggle_like_returns_correct_state(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id, 'likes_count' => 0]);

        $liked = $this->feedService->toggleLike($post, $user);
        $this->assertTrue($liked);
        $this->assertEquals(1, $post->fresh()->likes_count);

        $liked = $this->feedService->toggleLike($post, $user);
        $this->assertFalse($liked);
        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_feed_service_add_comment_increments_counter(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id, 'comments_count' => 0]);

        $comment = $this->feedService->addComment($post, $user, 'Test comment content.');

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals(1, $post->fresh()->comments_count);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTIFICATION SERVICE — UNIT TESTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_notification_service_creates_notification(): void
    {
        $user = User::factory()->create();

        $notif = $this->notificationService->notify(
            $user,
            NotificationService::TYPE_GENERAL,
            'Test Notification',
            'This is a test message.',
        );

        $this->assertInstanceOf(Notification::class, $notif);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => NotificationService::TYPE_GENERAL,
            'title' => 'Test Notification',
        ]);
    }

    public function test_unread_count_returns_correct_number(): void
    {
        $user = User::factory()->create();

        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'A', 'msg');
        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'B', 'msg');

        $this->assertEquals(2, $this->notificationService->countUnread($user));
    }

    public function test_mark_as_read_updates_read_at(): void
    {
        $user = User::factory()->create();
        $notif = $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'N', 'msg');

        $this->assertNull($notif->read_at);

        $this->notificationService->markAsRead($notif);

        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_mark_all_as_read_clears_all_unread(): void
    {
        $user = User::factory()->create();
        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'N1', 'msg');
        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'N2', 'msg');

        $this->assertEquals(2, $this->notificationService->countUnread($user));

        $this->notificationService->markAllAsRead($user);

        $this->assertEquals(0, $this->notificationService->countUnread($user));
    }

    // ══════════════════════════════════════════════════════════════════════
    //  NOTIFICATION AJAX ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════

    public function test_notification_ajax_index_returns_json(): void
    {
        $user = User::factory()->create();
        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'Hello', 'World');

        $this->actingAs($user)
            ->getJson('/ajax/notifications')
            ->assertOk()
            ->assertJsonStructure(['unread_count', 'notifications'])
            ->assertJsonPath('unread_count', 1);
    }

    public function test_notification_mark_all_read_ajax(): void
    {
        $user = User::factory()->create();
        $this->notificationService->notify($user, NotificationService::TYPE_GENERAL, 'N', 'msg');

        $this->actingAs($user)
            ->postJson('/ajax/notifications/mark-all-read')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertEquals(0, $this->notificationService->countUnread($user));
    }

    public function test_user_can_edit_own_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'Original text',
        ]);

        $response = $this->actingAs($user)
            ->patchJson(route('social.comments.update', $comment), [
                'content' => 'Updated text',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'content' => 'Updated text',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated text',
        ]);
    }

    public function test_user_cannot_edit_other_user_comment(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user1->id,
            'content' => 'User1 text',
        ]);

        $this->actingAs($user2)
            ->patchJson(route('social.comments.update', $comment), [
                'content' => 'Hacked text',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'User1 text',
        ]);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['comments_count' => 1]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'content' => 'To be deleted',
        ]);

        $response = $this->actingAs($user)
            ->deleteJson(route('social.comments.destroy', $comment));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'comments_count' => 0,
            ]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_post_author_can_delete_other_user_comment(): void
    {
        $postAuthor = User::factory()->create();
        $commenter = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id, 'comments_count' => 1]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Spam comment',
        ]);

        $response = $this->actingAs($postAuthor)
            ->deleteJson(route('social.comments.destroy', $comment));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'comments_count' => 0,
            ]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_unauthorized_user_cannot_delete_other_comment(): void
    {
        $postAuthor = User::factory()->create();
        $commenter = User::factory()->create();
        $stranger = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id, 'comments_count' => 1]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $commenter->id,
            'content' => 'Legit comment',
        ]);

        $this->actingAs($stranger)
            ->deleteJson(route('social.comments.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
