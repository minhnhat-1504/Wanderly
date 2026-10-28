<?php

namespace App\Services\Admin;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminModerationService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Submit a user report for a post or comment.
     *
     * @throws ValidationException
     */
    public function submitReport(User $reporter, Model $target, string $reason, ?string $details = null): Report
    {
        $targetUserId = $target->user_id ?? $target->author?->id ?? null;

        if ($targetUserId !== null && $reporter->id === $targetUserId) {
            throw ValidationException::withMessages([
                'report' => __('You cannot report your own content.'),
            ]);
        }

        $targetClass = get_class($target);

        $alreadyReported = Report::where('reporter_id', $reporter->id)
            ->where('reportable_type', $targetClass)
            ->where('reportable_id', $target->id)
            ->where('status', 'pending')
            ->exists();

        if ($alreadyReported) {
            throw ValidationException::withMessages([
                'report' => __('You have already submitted a pending report for this item.'),
            ]);
        }

        return Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $targetClass,
            'reportable_id' => $target->id,
            'reason' => $reason,
            'details' => $details,
            'status' => 'pending',
        ]);
    }

    /**
     * Get paginated reports with optional filtering.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? 'pending';
        $reason = $filters['reason'] ?? null;
        $targetType = $filters['target_type'] ?? null;
        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $query = Report::query()->with(['reporter', 'admin', 'reportable']);

        if (! empty($status) && $status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($reason)) {
            $query->where('reason', $reason);
        }

        if ($targetType === 'post') {
            $query->where('reportable_type', Post::class);
        } elseif ($targetType === 'comment') {
            $query->where('reportable_type', Comment::class);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * Aggregate report and content statistics for dashboard & filters.
     *
     * @return array<string, int>
     */
    public function getStats(): array
    {
        return [
            'pending' => Report::where('status', 'pending')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
            'total' => Report::count(),
            'hidden_posts' => Post::where('is_hidden', true)->count(),
            'hidden_comments' => Comment::where('is_hidden', true)->count(),
        ];
    }

    /**
     * Resolve a report with an action (hide, delete, dismiss, none) and optional note.
     */
    public function resolveReport(User $admin, Report $report, string $action, ?string $adminNote = null): Report
    {
        return DB::transaction(function () use ($admin, $report, $action, $adminNote) {
            $oldStatus = $report->status;
            $newStatus = ($action === 'dismiss') ? 'dismissed' : 'resolved';

            $report->update([
                'status' => $newStatus,
                'action_taken' => $action,
                'admin_id' => $admin->id,
                'admin_note' => $adminNote,
                'resolved_at' => now(),
            ]);

            $target = $report->reportable;

            if ($target) {
                if ($action === 'hide') {
                    if ($target instanceof Post) {
                        $target->update([
                            'is_hidden' => true,
                            'hidden_at' => now(),
                            'hidden_reason' => $report->reason,
                        ]);
                    } elseif ($target instanceof Comment) {
                        $target->update([
                            'is_hidden' => true,
                            'hidden_at' => now(),
                            'hidden_reason' => $report->reason,
                        ]);

                        if ($target->post && $target->post->comments_count > 0) {
                            $target->post->decrement('comments_count');
                        }
                    }
                } elseif ($action === 'delete') {
                    if ($target instanceof Post) {
                        $target->delete();
                    } elseif ($target instanceof Comment) {
                        $post = $target->post;
                        $target->delete();

                        if ($post && $post->comments_count > 0) {
                            $post->decrement('comments_count');
                        }
                    }
                }
            }

            // Record audit log
            $this->auditLogService->record(
                admin: $admin,
                action: 'report.resolve',
                subject: $report,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus, 'action_taken' => $action],
                metadata: [
                    'reportable_type' => $report->reportable_type,
                    'reportable_id' => $report->reportable_id,
                    'reason' => $report->reason,
                ]
            );

            // Notify reporter
            $this->notificationService->notifyReportResolved($report->reporter, $report);

            // Notify content author if content was hidden or deleted
            if ($target && in_array($action, ['hide', 'delete'])) {
                $author = $target->author ?? User::find($target->user_id);
                if ($author) {
                    $this->notificationService->notifyContentModerated(
                        author: $author,
                        contentType: class_basename($report->reportable_type),
                        action: $action,
                        reason: $report->reason
                    );
                }
            }

            return $report;
        });
    }

    /**
     * Dismiss a report without penalizing content.
     */
    public function dismissReport(User $admin, Report $report, ?string $adminNote = null): Report
    {
        return $this->resolveReport($admin, $report, 'dismiss', $adminNote);
    }

    /**
     * Toggle visibility of a post.
     */
    public function toggleHidePost(User $admin, Post $post, ?string $reason = null): Post
    {
        return DB::transaction(function () use ($admin, $post, $reason) {
            $isCurrentlyHidden = (bool) $post->is_hidden;
            $newHiddenState = ! $isCurrentlyHidden;

            $post->update([
                'is_hidden' => $newHiddenState,
                'hidden_at' => $newHiddenState ? now() : null,
                'hidden_reason' => $newHiddenState ? ($reason ?: __('Moderated by administrator')) : null,
            ]);

            $this->auditLogService->record(
                admin: $admin,
                action: 'post.toggle_hide',
                subject: $post,
                oldValues: ['is_hidden' => $isCurrentlyHidden],
                newValues: ['is_hidden' => $newHiddenState],
                metadata: ['post_id' => $post->id, 'title' => $post->title]
            );

            if ($newHiddenState && $post->author) {
                $this->notificationService->notifyContentModerated(
                    author: $post->author,
                    contentType: 'Post',
                    action: 'hide',
                    reason: $reason ?: __('Moderated by administrator')
                );
            }

            return $post;
        });
    }

    /**
     * Toggle visibility of a comment and synchronize comments_count.
     */
    public function toggleHideComment(User $admin, Comment $comment, ?string $reason = null): Comment
    {
        return DB::transaction(function () use ($admin, $comment, $reason) {
            $isCurrentlyHidden = (bool) $comment->is_hidden;
            $newHiddenState = ! $isCurrentlyHidden;

            $comment->update([
                'is_hidden' => $newHiddenState,
                'hidden_at' => $newHiddenState ? now() : null,
                'hidden_reason' => $newHiddenState ? ($reason ?: __('Moderated by administrator')) : null,
            ]);

            if ($comment->post) {
                if ($newHiddenState && $comment->post->comments_count > 0) {
                    $comment->post->decrement('comments_count');
                } elseif (! $newHiddenState) {
                    $comment->post->increment('comments_count');
                }
            }

            $this->auditLogService->record(
                admin: $admin,
                action: 'comment.toggle_hide',
                subject: $comment,
                oldValues: ['is_hidden' => $isCurrentlyHidden],
                newValues: ['is_hidden' => $newHiddenState],
                metadata: ['comment_id' => $comment->id, 'post_id' => $comment->post_id]
            );

            if ($newHiddenState && $comment->author) {
                $this->notificationService->notifyContentModerated(
                    author: $comment->author,
                    contentType: 'Comment',
                    action: 'hide',
                    reason: $reason ?: __('Moderated by administrator')
                );
            }

            return $comment;
        });
    }

    /**
     * List posts for content moderation tab.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? 'all';
        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $query = Post::query()->with(['author', 'trip'])->withCount(['comments', 'reports']);

        if ($status === 'hidden') {
            $query->where('is_hidden', true);
        } elseif ($status === 'active') {
            $query->where('is_hidden', false);
        } elseif ($status === 'trashed') {
            $query->onlyTrashed();
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('author', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    /**
     * List comments for content moderation tab.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listComments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $status = $filters['status'] ?? 'all';
        $search = isset($filters['search']) ? trim($filters['search']) : null;

        $query = Comment::query()->with(['author', 'post'])->withCount(['reports']);

        if ($status === 'hidden') {
            $query->where('is_hidden', true);
        } elseif ($status === 'active') {
            $query->where('is_hidden', false);
        } elseif ($status === 'trashed') {
            $query->onlyTrashed();
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                    ->orWhereHas('author', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }
}
