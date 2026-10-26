<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SocialFeedService
{
    /**
     * Get paginated community feed posts with eager-loaded relations.
     *
     * @return LengthAwarePaginator<Post>
     */
    public function getFeed(int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['author', 'trip'])
            ->withCount(['comments', 'likedByUsers'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get posts saved by a specific user.
     *
     * @return LengthAwarePaginator<Post>
     */
    public function getSavedPosts(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Post::with(['author', 'trip'])
            ->withCount(['comments', 'likedByUsers'])
            ->whereHas('savedByUsers', fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new community post, optionally attaching a trip card.
     */
    public function createPost(User $author, array $data): Post
    {
        return DB::transaction(function () use ($author, $data) {
            return Post::create([
                'user_id' => $author->id,
                'trip_id' => $data['trip_id'] ?? null,
                'title' => $data['title'],
                'content' => $data['content'],
                'images' => $data['images'] ?? null,
            ]);
        });
    }

    /**
     * Delete a post (only by its author).
     */
    public function deletePost(Post $post, User $actor): bool
    {
        if ($post->user_id !== $actor->id) {
            abort(403, __('You are not authorized to delete this post.'));
        }

        return (bool) $post->delete();
    }

    /**
     * Toggle like on a post. Returns true if liked, false if unliked.
     */
    public function toggleLike(Post $post, User $user): bool
    {
        $alreadyLiked = $post->isLikedBy($user);

        if ($alreadyLiked) {
            $post->likedByUsers()->detach($user->id);
            $post->decrement('likes_count');

            return false;
        }

        $post->likedByUsers()->attach($user->id);
        $post->increment('likes_count');

        return true;
    }

    /**
     * Add a comment to a post.
     */
    public function addComment(Post $post, User $author, string $content): Comment
    {
        return DB::transaction(function () use ($post, $author, $content) {
            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => $author->id,
                'content' => $content,
            ]);

            $post->increment('comments_count');

            return $comment->load('author');
        });
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Comment $comment, User $actor): bool
    {
        if ($comment->user_id !== $actor->id && $comment->post->user_id !== $actor->id) {
            abort(403, __('You are not authorized to delete this comment.'));
        }

        $post = $comment->post;
        $deleted = (bool) $comment->delete();

        if ($deleted && $post->comments_count > 0) {
            $post->decrement('comments_count');
        }

        return $deleted;
    }

    /**
     * Toggle bookmark/save on a post.
     */
    public function toggleSave(Post $post, User $user): bool
    {
        $alreadySaved = $post->isSavedBy($user);

        if ($alreadySaved) {
            $post->savedByUsers()->detach($user->id);

            return false;
        }

        $post->savedByUsers()->attach($user->id);

        return true;
    }

    /**
     * Clone a trip from a post into the current user's account.
     */
    public function cloneTrip(Post $post, User $newOwner, TripService $tripService): ?Trip
    {
        if (! $post->trip) {
            return null;
        }

        return $tripService->duplicateTrip($post->trip, $newOwner);
    }
}
