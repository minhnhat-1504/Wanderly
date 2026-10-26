<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ChatService
{
    public const MESSAGE_BATCH_LIMIT = 50;

    public const ATTACHMENT_DIRECTORY = 'chat';

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Trip group chat is open to the trip owner and every trip member.
     */
    public function canAccessTripChat(User $user, Trip $trip): bool
    {
        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Direct (1-1) booking chat is open to the traveler side (trip owner / leaders) and the booked provider.
     */
    public function canAccessBookingChat(User $user, Booking $booking): bool
    {
        if ($booking->provider?->user_id === $user->id) {
            return true;
        }

        $trip = $booking->trip;

        if (! $trip) {
            return false;
        }

        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()
            ->where('user_id', $user->id)
            ->where('role', 'leader')
            ->exists();
    }

    /**
     * Post a message to the trip group channel.
     */
    public function sendTripMessage(Trip $trip, User $sender, ?string $content, ?UploadedFile $attachment = null): Message
    {
        if (! $this->canAccessTripChat($sender, $trip)) {
            throw new AuthorizationException(__('You are not a member of this trip chat.'));
        }

        return Message::create([
            'trip_id' => $trip->id,
            'booking_id' => null,
            'sender_id' => $sender->id,
            'receiver_id' => null,
            'content' => $this->sanitizeContent($content, $attachment),
            'attachment' => $this->storeAttachment($attachment),
            'is_read' => false,
        ])->load('sender:id,name,avatar');
    }

    /**
     * Post a direct message between the traveler and the provider of a booking.
     */
    public function sendDirectMessage(Booking $booking, User $sender, ?string $content, ?UploadedFile $attachment = null): Message
    {
        if (! $this->canAccessBookingChat($sender, $booking)) {
            throw new AuthorizationException(__('You are not a participant of this booking conversation.'));
        }

        $booking->loadMissing(['provider.user', 'trip.owner']);

        $senderIsProvider = $booking->provider?->user_id === $sender->id;
        $receiver = $senderIsProvider ? $booking->trip?->owner : $booking->provider?->user;

        $message = Message::create([
            'trip_id' => $booking->trip_id,
            'booking_id' => $booking->id,
            'sender_id' => $sender->id,
            'receiver_id' => $receiver?->id,
            'content' => $this->sanitizeContent($content, $attachment),
            'attachment' => $this->storeAttachment($attachment),
            'is_read' => false,
        ])->load('sender:id,name,avatar');

        if ($receiver && $receiver->id !== $sender->id) {
            $this->notifyReceiver($receiver, $sender, $booking, $message, $senderIsProvider);
        }

        return $message;
    }

    /**
     * Fetch messages of a channel. Without $lastMessageId the latest batch (history) is returned,
     * otherwise only messages newer than $lastMessageId (AJAX polling).
     *
     * @param  array{trip_id?: int, booking_id?: int}  $filters
     * @return Collection<int, Message>
     */
    public function fetchMessages(array $filters, ?int $lastMessageId = null): Collection
    {
        $query = Message::query()->with('sender:id,name,avatar');

        if (! empty($filters['booking_id'])) {
            $query->forBooking((int) $filters['booking_id']);
        } elseif (! empty($filters['trip_id'])) {
            $query->forTrip((int) $filters['trip_id']);
        } else {
            throw new InvalidArgumentException('A trip_id or booking_id filter is required.');
        }

        if ($lastMessageId) {
            return $query->where('id', '>', $lastMessageId)
                ->orderBy('id')
                ->limit(self::MESSAGE_BATCH_LIMIT)
                ->get()
                ->toBase();
        }

        return $query->orderByDesc('id')
            ->limit(self::MESSAGE_BATCH_LIMIT)
            ->get()
            ->sortBy('id')
            ->values()
            ->toBase();
    }

    /**
     * Mark every direct message addressed to the user in a booking channel as read.
     */
    public function markBookingChannelAsRead(Booking $booking, User $user): int
    {
        return Message::query()
            ->forBooking($booking->id)
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Total unread direct messages addressed to the user.
     * Group messages have no single receiver, so their unread state is tracked client-side.
     */
    public function getUnreadCount(User $user): int
    {
        return Message::query()
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Direct-chat channels (bookings) of a trip that the user may access, with unread counters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getTripDirectChannels(Trip $trip, User $user): Collection
    {
        $bookings = $trip->bookings()
            ->with('provider:id,user_id,business_name,service_type')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->get()
            ->filter(fn (Booking $booking) => $this->canAccessBookingChat($user, $booking));

        return $this->mapChannels($bookings, $user, fn (Booking $booking) => $booking->provider?->business_name ?? __('Provider'));
    }

    /**
     * Direct-chat channels (bookings) of a provider, with unread counters.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getProviderDirectChannels(Provider $provider, User $user): Collection
    {
        $bookings = $provider->bookings()
            ->with('trip:id,name,owner_id', 'trip.owner:id,name')
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->get();

        return $this->mapChannels($bookings, $user, fn (Booking $booking) => $booking->trip?->owner?->name ?? __('Traveler'));
    }

    /**
     * Shape a message for the JSON API consumed by chat.js.
     *
     * @return array<string, mixed>
     */
    public function present(Message $message, User $viewer): array
    {
        $senderName = $message->sender?->name ?? __('Unknown');

        return [
            'id' => $message->id,
            'content' => $message->content,
            'attachment_url' => $message->attachment_url,
            'is_mine' => $message->sender_id === $viewer->id,
            'is_read' => $message->is_read,
            'sender' => [
                'id' => $message->sender_id,
                'name' => $senderName,
                'initial' => Str::upper(Str::substr($senderName, 0, 1)),
            ],
            'created_at' => $message->created_at?->toIso8601String(),
            'time_label' => $message->created_at?->format('H:i'),
            'date_label' => $message->created_at?->format('d M Y'),
        ];
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return Collection<int, array<string, mixed>>
     */
    protected function mapChannels(Collection $bookings, User $user, callable $counterpartName): Collection
    {
        $unread = Message::query()
            ->whereIn('booking_id', $bookings->pluck('id'))
            ->where('receiver_id', $user->id)
            ->where('is_read', false)
            ->selectRaw('booking_id, COUNT(*) as total')
            ->groupBy('booking_id')
            ->pluck('total', 'booking_id');

        return $bookings->map(fn (Booking $booking) => [
            'booking_id' => $booking->id,
            'title' => $booking->item_name,
            'counterpart' => $counterpartName($booking),
            'voucher_code' => $booking->voucher_code,
            'status' => $booking->status,
            'unread' => (int) ($unread[$booking->id] ?? 0),
        ])->values();
    }

    protected function sanitizeContent(?string $content, ?UploadedFile $attachment): string
    {
        $content = trim((string) $content);

        if ($content === '' && ! $attachment) {
            throw new InvalidArgumentException(__('A message must contain text or an image.'));
        }

        return Str::limit($content, 2000, '');
    }

    protected function storeAttachment(?UploadedFile $attachment): ?string
    {
        return $attachment?->store(self::ATTACHMENT_DIRECTORY, 'public');
    }

    /**
     * Send one in-app notification per unread conversation to avoid flooding the bell.
     */
    protected function notifyReceiver(User $receiver, User $sender, Booking $booking, Message $message, bool $senderIsProvider): void
    {
        $actionUrl = $senderIsProvider
            ? route('trips.show', $booking->trip_id).'?chat=booking-'.$booking->id
            : route('provider.bookings.show', $booking);

        $alreadyNotified = Notification::query()
            ->where('user_id', $receiver->id)
            ->where('type', NotificationService::TYPE_NEW_MESSAGE)
            ->where('action_url', $actionUrl)
            ->whereNull('read_at')
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $preview = $message->content !== '' ? Str::limit($message->content, 80) : __('Sent an image.');

        $this->notificationService->notify(
            $receiver,
            NotificationService::TYPE_NEW_MESSAGE,
            __('New message from :name', ['name' => $sender->name]),
            __(':item — :preview', ['item' => $booking->item_name, 'preview' => $preview]),
            $actionUrl,
        );
    }
}
