<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * Get recent notifications for the current user (AJAX).
     */
    public function index(): JsonResponse
    {
        $user = auth()->user();

        $notifications = $this->notificationService->getRecent($user, 20);
        $unreadCount = $this->notificationService->countUnread($user);

        return response()->json([
            'unread_count' => $unreadCount,
            'notifications' => $notifications->map(fn (Notification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'message' => $n->message,
                'action_url' => $n->action_url,
                'is_read' => $n->isRead(),
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /**
     * Mark a single notification as read (AJAX).
     */
    public function markRead(Notification $notification): JsonResponse
    {
        // Ensure user owns this notification
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['error' => __('Unauthorized')], 403);
        }

        $this->notificationService->markAsRead($notification);

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read (AJAX).
     */
    public function markAllRead(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
            'marked' => $count,
        ]);
    }
}
