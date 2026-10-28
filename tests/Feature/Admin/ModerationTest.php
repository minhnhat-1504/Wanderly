<?php

namespace Tests\Feature\Admin;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\SocialFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $author;

    protected User $reporter;

    protected Post $post;

    protected Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@wanderly.test',
        ]);

        $this->author = User::factory()->create([
            'role' => 'traveler',
            'email' => 'author@wanderly.test',
        ]);

        $this->reporter = User::factory()->create([
            'role' => 'traveler',
            'email' => 'reporter@wanderly.test',
        ]);

        $this->post = Post::create([
            'user_id' => $this->author->id,
            'title' => 'Exploration of Sapa Terraces',
            'content' => 'Join our exclusive trip! Check out the commercial links below.',
            'likes_count' => 0,
            'comments_count' => 1,
        ]);

        $this->comment = Comment::create([
            'post_id' => $this->post->id,
            'user_id' => $this->author->id,
            'content' => 'Call 0901234567 for discount vouchers.',
        ]);
    }

    public function test_guest_cannot_access_moderation_portal(): void
    {
        $response = $this->get('/admin/moderation');
        $response->assertRedirect('/login');
    }

    public function test_traveler_and_provider_receive_403_from_moderation_portal(): void
    {
        $response = $this->actingAs($this->reporter)->get('/admin/moderation');
        $response->assertStatus(403);

        $provider = User::factory()->create(['role' => 'provider']);
        $response2 = $this->actingAs($provider)->get('/admin/moderation');
        $response2->assertStatus(403);
    }

    public function test_user_can_submit_report_on_post(): void
    {
        $response = $this->actingAs($this->reporter)->post('/social/posts/'.$this->post->id.'/report', [
            'reason' => 'spam',
            'details' => 'Contains suspicious commercial advertising links.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_report_their_own_post(): void
    {
        $response = $this->actingAs($this->author)->post('/social/posts/'.$this->post->id.'/report', [
            'reason' => 'spam',
        ]);

        $response->assertSessionHasErrors(['report']);
        $this->assertDatabaseMissing('reports', [
            'reporter_id' => $this->author->id,
            'reportable_id' => $this->post->id,
        ]);
    }

    public function test_user_cannot_report_their_own_comment(): void
    {
        $response = $this->actingAs($this->author)->post('/social/comments/'.$this->comment->id.'/report', [
            'reason' => 'harassment',
        ]);

        $response->assertSessionHasErrors(['report']);
    }

    public function test_user_cannot_submit_duplicate_pending_report(): void
    {
        // First report succeeds
        $this->actingAs($this->reporter)->post('/social/posts/'.$this->post->id.'/report', [
            'reason' => 'spam',
        ]);

        // Second report on same post fails
        $response = $this->actingAs($this->reporter)->post('/social/posts/'.$this->post->id.'/report', [
            'reason' => 'inappropriate',
        ]);

        $response->assertSessionHasErrors(['report']);
        $this->assertEquals(1, Report::where('reportable_id', $this->post->id)->count());
    }

    public function test_user_can_submit_report_on_comment(): void
    {
        $response = $this->actingAs($this->reporter)->post('/social/comments/'.$this->comment->id.'/report', [
            'reason' => 'spam',
            'details' => 'Unsolicited phone number promo.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $this->comment->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_reports_and_filter(): void
    {
        Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'spam',
            'details' => 'Spam content inside.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/moderation');
        $response->assertOk();
        $response->assertSee('Exploration of Sapa Terraces');
        $response->assertSee('Spam content inside.');

        // Test status filter
        $responseResolved = $this->actingAs($this->admin)->get('/admin/moderation?status=resolved');
        $responseResolved->assertOk();
        $responseResolved->assertDontSee('Spam content inside.');
    }

    public function test_admin_can_resolve_report_and_hide_post(): void
    {
        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'spam',
            'details' => 'Severe spamming.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/moderation/reports/'.$report->id.'/resolve', [
            'action' => 'hide',
            'admin_note' => 'Confirmed violation of commercial guidelines.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals('resolved', $report->status);
        $this->assertEquals('hide', $report->action_taken);
        $this->assertEquals($this->admin->id, $report->admin_id);

        $this->post->refresh();
        $this->assertTrue($this->post->is_hidden);
        $this->assertEquals('spam', $this->post->hidden_reason);

        // Verify post is hidden from community feed
        $feedService = app(SocialFeedService::class);
        $feed = $feedService->getFeed();
        $this->assertFalse($feed->contains('id', $this->post->id));

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'report.resolve',
            'subject_id' => $report->id,
        ]);

        // Notifications
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->reporter->id,
            'type' => 'report_resolved',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->author->id,
            'type' => 'content_moderated',
        ]);
    }

    public function test_admin_can_resolve_report_and_delete_comment(): void
    {
        $this->assertEquals(1, $this->post->comments_count);

        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $this->comment->id,
            'reason' => 'harassment',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/moderation/reports/'.$report->id.'/resolve', [
            'action' => 'delete',
            'admin_note' => 'Inappropriate harassment language removed.',
        ]);

        $response->assertRedirect();

        $this->assertSoftDeleted('comments', ['id' => $this->comment->id]);

        $this->post->refresh();
        $this->assertEquals(0, $this->post->comments_count);
    }

    public function test_admin_can_dismiss_report(): void
    {
        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'other',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/moderation/reports/'.$report->id.'/dismiss', [
            'admin_note' => 'Content adheres to community guidelines.',
        ]);

        $response->assertRedirect();

        $report->refresh();
        $this->assertEquals('dismissed', $report->status);

        $this->post->refresh();
        $this->assertFalse($this->post->is_hidden);

        // Audit log
        $this->assertDatabaseHas('audit_logs', [
            'admin_id' => $this->admin->id,
            'action' => 'report.resolve',
            'subject_id' => $report->id,
        ]);
    }

    public function test_admin_can_toggle_hide_post_directly(): void
    {
        $this->assertFalse($this->post->is_hidden);

        // Toggle to hidden
        $this->actingAs($this->admin)->post('/admin/moderation/posts/'.$this->post->id.'/toggle-hide', [
            'reason' => 'Manual moderator intervention',
        ]);

        $this->post->refresh();
        $this->assertTrue($this->post->is_hidden);

        // Toggle back to visible
        $this->actingAs($this->admin)->post('/admin/moderation/posts/'.$this->post->id.'/toggle-hide');
        $this->post->refresh();
        $this->assertFalse($this->post->is_hidden);
    }

    public function test_admin_can_toggle_hide_comment_directly(): void
    {
        $this->assertEquals(1, $this->post->comments_count);

        // Hide comment -> post comments_count drops
        $this->actingAs($this->admin)->post('/admin/moderation/comments/'.$this->comment->id.'/toggle-hide');
        $this->comment->refresh();
        $this->post->refresh();
        $this->assertTrue($this->comment->is_hidden);
        $this->assertEquals(0, $this->post->comments_count);

        // Unhide comment -> post comments_count increments back
        $this->actingAs($this->admin)->post('/admin/moderation/comments/'.$this->comment->id.'/toggle-hide');
        $this->comment->refresh();
        $this->post->refresh();
        $this->assertFalse($this->comment->is_hidden);
        $this->assertEquals(1, $this->post->comments_count);
    }

    public function test_admin_can_view_content_moderation_tab(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/moderation/content?type=posts');
        $response->assertOk();
        $response->assertSee($this->post->title);

        $responseComments = $this->actingAs($this->admin)->get('/admin/moderation/content?type=comments');
        $responseComments->assertOk();
        $responseComments->assertSee($this->comment->content);
    }

    public function test_reporter_can_view_report_review_status_page(): void
    {
        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'harassment',
            'details' => 'Offensive behavior targeting members',
            'status' => 'resolved',
            'action_taken' => 'delete',
            'admin_id' => $this->admin->id,
            'admin_note' => 'We removed this content. Thank you for reporting.',
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($this->reporter)->get('/social/reports/'.$report->id);
        $response->assertOk();
        $response->assertSee('Report Review Details');
        $response->assertSee('Resolved');
        $response->assertSee('Content Removed Permanently');
        $response->assertSee('We removed this content. Thank you for reporting.');
    }

    public function test_unauthorized_user_cannot_view_report_review_status_page(): void
    {
        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'harassment',
            'status' => 'resolved',
            'action_taken' => 'delete',
            'resolved_at' => now(),
        ]);

        $otherTraveler = User::factory()->create(['role' => 'traveler']);
        $response = $this->actingAs($otherTraveler)->get('/social/reports/'.$report->id);
        $response->assertStatus(403);
    }

    public function test_resolving_report_notifies_reporter_with_deep_link(): void
    {
        $report = Report::create([
            'reporter_id' => $this->reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $this->post->id,
            'reason' => 'spam',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/moderation/reports/'.$report->id.'/resolve', [
            'action' => 'hide',
            'admin_note' => 'Hidden from feed due to spam link.',
        ]);
        $response->assertRedirect();

        $notification = Notification::where('user_id', $this->reporter->id)
            ->where('type', 'report_resolved')
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('/social/reports/'.$report->id, $notification->action_url);
    }
}
