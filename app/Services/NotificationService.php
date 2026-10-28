<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\Notification;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    // Notification type constants
    const TYPE_BUDGET_WARNING = 'budget_warning';

    const TYPE_TRIP_INVITE = 'trip_invite';

    const TYPE_NEW_PROPOSAL = 'new_proposal';

    const TYPE_BOOKING_UPDATE = 'booking_update';

    const TYPE_NEW_MESSAGE = 'new_message';

    const TYPE_NEW_COMMENT = 'new_comment';

    const TYPE_POST_LIKE = 'post_like';

    const TYPE_ACCOUNT_SUSPENDED = 'account_suspended';

    const TYPE_ACCOUNT_UNSUSPENDED = 'account_unsuspended';

    const TYPE_ROLE_CHANGED = 'role_changed';

    const TYPE_REPORT_RESOLVED = 'report_resolved';

    const TYPE_CONTENT_MODERATED = 'content_moderated';

    const TYPE_BROADCAST = 'broadcast';

    const TYPE_GENERAL = 'general';

    /**
     * Create and persist a new in-app notification for a user.
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?int $broadcastId = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'broadcast_id' => $broadcastId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'read_at' => null,
        ]);
    }

    /**
     * Notify user about budget threshold breach.
     */
    public function notifyBudgetWarning(User $user, string $tripName, float $percentage, string $tripUrl): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_BUDGET_WARNING,
            __('Budget Alert — :trip', ['trip' => $tripName]),
            __('Your trip budget is at :pct% capacity. Consider adjusting your spending.', ['pct' => round($percentage)]),
            $tripUrl,
        );
    }

    /**
     * Notify user about receiving a new service proposal.
     */
    public function notifyNewProposal(User $user, string $providerName, string $tripName, string $compareUrl): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_NEW_PROPOSAL,
            __('New Proposal Received'),
            __(':provider submitted a proposal for your :trip request.', ['provider' => $providerName, 'trip' => $tripName]),
            $compareUrl,
        );
    }

    /**
     * Notify user about a booking status update.
     */
    public function notifyBookingUpdate(User $user, string $itemName, string $status, string $bookingUrl): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_BOOKING_UPDATE,
            __('Booking Update — :item', ['item' => $itemName]),
            __('Your booking status has been updated to: :status.', ['status' => ucfirst($status)]),
            $bookingUrl,
        );
    }

    /**
     * Notify user about a new comment on their post.
     */
    public function notifyNewComment(User $postAuthor, string $commenterName, string $postTitle, string $postUrl): Notification
    {
        return $this->notify(
            $postAuthor,
            self::TYPE_NEW_COMMENT,
            __('New Comment on Your Post'),
            __(':name commented on ":title".', ['name' => $commenterName, 'title' => $postTitle]),
            $postUrl,
        );
    }

    /**
     * Notify user that someone liked their post.
     */
    public function notifyPostLike(User $postAuthor, string $likerName, string $postTitle, string $postUrl): Notification
    {
        return $this->notify(
            $postAuthor,
            self::TYPE_POST_LIKE,
            __(':name liked your post', ['name' => $likerName]),
            __('":title" is getting popular!', ['title' => $postTitle]),
            $postUrl,
        );
    }

    /**
     * Notify user that their account was suspended.
     */
    public function notifyAccountSuspended(User $user, string $reason): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_ACCOUNT_SUSPENDED,
            __('Account Suspended'),
            __('Your account has been suspended. Reason: :reason', ['reason' => $reason]),
            null,
        );
    }

    /**
     * Notify user that their account was unsuspended.
     */
    public function notifyAccountUnsuspended(User $user): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_ACCOUNT_UNSUSPENDED,
            __('Account Restored'),
            __('Your account suspension has been lifted. Welcome back to Wanderly!'),
            null,
        );
    }

    /**
     * Notify user that their account role has changed.
     */
    public function notifyRoleChanged(User $user, string $newRole): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_ROLE_CHANGED,
            __('Role Updated'),
            __('Your account role has been updated to :role.', ['role' => ucfirst($newRole)]),
            null,
        );
    }

    /**
     * Notify reporter that their submitted report was reviewed and resolved.
     */
    public function notifyReportResolved(User $reporter, Report $report): Notification
    {
        $actionSummary = match ($report->action_taken) {
            'delete' => __('The reported content was removed.'),
            'hide' => __('The reported content was hidden from public view.'),
            'suspend_user' => __('Disciplinary action was taken against the author.'),
            'dismiss' => __('The report was reviewed and dismissed (no violation found).'),
            default => __('Your report has been processed by our moderation team.'),
        };

        return $this->notify(
            $reporter,
            self::TYPE_REPORT_RESOLVED,
            __('Report Reviewed & Resolved'),
            __('Your report regarding :reason has been reviewed: :action Click to inspect full resolution details.', [
                'reason' => $report->reason,
                'action' => $actionSummary,
            ]),
            route('social.reports.show', $report->id),
        );
    }

    /**
     * Notify author that their content was moderated.
     */
    public function notifyContentModerated(User $author, string $contentType, string $action, string $reason): Notification
    {
        return $this->notify(
            $author,
            self::TYPE_CONTENT_MODERATED,
            __('Community Guidelines Notice'),
            __('Your :type has been :action due to a violation of community standards (:reason).', [
                'type' => strtolower($contentType),
                'action' => $action === 'delete' ? __('deleted') : __('hidden from public view'),
                'reason' => $reason,
            ]),
            null,
        );
    }

    /**
     * Send a broadcast announcement notification to a user.
     */
    public function sendBroadcastToUser(User $user, Broadcast $broadcast): Notification
    {
        return $this->notify(
            $user,
            self::TYPE_BROADCAST,
            $broadcast->title,
            $broadcast->message,
            $broadcast->action_url,
            $broadcast->id,
        );
    }

    /**
     * Get all unread notifications for a user.
     *
     * @return Collection<int, Notification>
     */
    public function getUnread(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnread(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get recent notifications (read + unread) for the dropdown panel.
     *
     * @return Collection<int, Notification>
     */
    public function getRecent(User $user, int $limit = 20): Collection
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Notification $notification): Notification
    {
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    /**
     * Mark all unread notifications as read for a user.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
