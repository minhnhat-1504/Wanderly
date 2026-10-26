<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Trip;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    /**
     * Trip group chat history / polling feed.
     */
    public function tripMessages(Request $request, Trip $trip): JsonResponse
    {
        abort_unless($this->chatService->canAccessTripChat($request->user(), $trip), 403, __('You are not a member of this trip chat.'));

        $messages = $this->chatService->fetchMessages(['trip_id' => $trip->id], $this->afterId($request));

        return $this->messagesResponse($request, $messages);
    }

    /**
     * Send a message to the trip group chat.
     */
    public function sendTripMessage(SendMessageRequest $request, Trip $trip): JsonResponse
    {
        abort_unless($this->chatService->canAccessTripChat($request->user(), $trip), 403, __('You are not a member of this trip chat.'));

        $message = $this->chatService->sendTripMessage(
            $trip,
            $request->user(),
            $request->validated('content'),
            $request->file('attachment'),
        );

        return response()->json([
            'success' => true,
            'message' => $this->chatService->present($message, $request->user()),
        ], 201);
    }

    /**
     * Direct-chat channels (bookings) available to the user inside a trip.
     */
    public function tripChannels(Request $request, Trip $trip): JsonResponse
    {
        abort_unless($this->chatService->canAccessTripChat($request->user(), $trip), 403, __('You are not a member of this trip chat.'));

        return response()->json([
            'success' => true,
            'channels' => $this->chatService->getTripDirectChannels($trip, $request->user()),
        ]);
    }

    /**
     * Booking 1-1 chat history / polling feed. Incoming messages are marked as read.
     */
    public function bookingMessages(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($this->chatService->canAccessBookingChat($request->user(), $booking), 403, __('You are not a participant of this booking conversation.'));

        $messages = $this->chatService->fetchMessages(['booking_id' => $booking->id], $this->afterId($request));

        if ($messages->isNotEmpty()) {
            $this->chatService->markBookingChannelAsRead($booking, $request->user());
        }

        return $this->messagesResponse($request, $messages);
    }

    /**
     * Send a direct message inside a booking conversation.
     */
    public function sendBookingMessage(SendMessageRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($this->chatService->canAccessBookingChat($request->user(), $booking), 403, __('You are not a participant of this booking conversation.'));

        $message = $this->chatService->sendDirectMessage(
            $booking,
            $request->user(),
            $request->validated('content'),
            $request->file('attachment'),
        );

        return response()->json([
            'success' => true,
            'message' => $this->chatService->present($message, $request->user()),
        ], 201);
    }

    /**
     * Total unread direct messages for the navbar / drawer badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'unread' => $this->chatService->getUnreadCount($request->user()),
        ]);
    }

    protected function afterId(Request $request): ?int
    {
        $afterId = (int) $request->query('after_id', 0);

        return $afterId > 0 ? $afterId : null;
    }

    protected function messagesResponse(Request $request, $messages): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'messages' => $messages->map(fn (Message $message) => $this->chatService->present($message, $user))->values(),
            'unread' => $this->chatService->getUnreadCount($user),
        ]);
    }
}
