<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\NotificationService;
use App\Services\SocialFeedService;
use App\Services\TripService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialFeedController extends Controller
{
    public function __construct(
        protected SocialFeedService $feedService,
        protected NotificationService $notificationService,
        protected TripService $tripService,
    ) {}

    /**
     * Display the community social feed.
     */
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'feed');

        if ($tab === 'saved') {
            $posts = $this->feedService->getSavedPosts(auth()->user());
        } else {
            $posts = $this->feedService->getFeed();
        }

        $myTrips = auth()->user()->trips()->latest()->get(['id', 'name', 'destination']);

        return view('social.feed', compact('posts', 'tab', 'myTrips'));
    }

    /**
     * Store a new community post.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'trip_id' => ['nullable', 'exists:trips,id'],
        ]);

        $this->feedService->createPost(auth()->user(), $validated);

        return redirect()->route('social.feed')->with('success', __('Your post has been shared with the community!'));
    }

    /**
     * Delete a post.
     */
    public function destroy(Post $post): RedirectResponse
    {
        $this->feedService->deletePost($post, auth()->user());

        return redirect()->route('social.feed')->with('success', __('Post deleted successfully.'));
    }

    /**
     * Toggle like on a post (AJAX).
     */
    public function toggleLike(Post $post): JsonResponse
    {
        $liked = $this->feedService->toggleLike($post, auth()->user());

        // Notify post author (skip self-like)
        if ($liked && $post->user_id !== auth()->id()) {
            $this->notificationService->notifyPostLike(
                $post->author,
                auth()->user()->name,
                $post->title,
                route('social.feed'),
            );
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    /**
     * Add a comment to a post (AJAX).
     */
    public function addComment(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $comment = $this->feedService->addComment($post, auth()->user(), $validated['content']);

        // Notify post author (skip self-comment)
        if ($post->user_id !== auth()->id()) {
            $this->notificationService->notifyNewComment(
                $post->author,
                auth()->user()->name,
                $post->title,
                route('social.feed'),
            );
        }

        return response()->json([
            'comment' => [
                'id' => $comment->id,
                'content' => $comment->content,
                'author' => $comment->author->name,
                'avatar_initial' => strtoupper(substr($comment->author->name, 0, 1)),
                'created_at' => $comment->created_at->diffForHumans(),
            ],
            'comments_count' => $post->fresh()->comments_count,
        ]);
    }

    /**
     * Toggle save/bookmark on a post (AJAX).
     */
    public function toggleSave(Post $post): JsonResponse
    {
        $saved = $this->feedService->toggleSave($post, auth()->user());

        return response()->json(['saved' => $saved]);
    }

    /**
     * Clone a trip from a post into the current user's account.
     */
    public function cloneTrip(Post $post): RedirectResponse
    {
        $newTrip = $this->feedService->cloneTrip($post, auth()->user(), $this->tripService);

        if (! $newTrip) {
            return back()->with('error', __('This post has no attached trip to clone.'));
        }

        return redirect()->route('trips.show', $newTrip)
            ->with('success', __('Trip cloned to your account successfully!'));
    }
}
