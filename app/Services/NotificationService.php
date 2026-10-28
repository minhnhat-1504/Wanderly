<?php

namespace App\Services;

use App\Models\Notification;
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

    const TYPE_GENERAL = 'general';

    /**
     * Create and persist a new in-app notification for a user.
     */
    public function notify(
        User $user,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
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
