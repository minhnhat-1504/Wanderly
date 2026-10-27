<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Provider;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;

    protected User $editor;

    protected User $outsider;

    protected User $providerUser;

    protected Provider $provider;

    protected Trip $trip;

    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create(['name' => 'Trip Leader']);
        $this->editor = User::factory()->create(['name' => 'Trip Editor']);
        $this->outsider = User::factory()->create(['name' => 'Outsider User']);
        $this->providerUser = User::factory()->create(['name' => 'Provider User', 'role' => 'provider']);

        $this->provider = Provider::factory()->create([
            'user_id' => $this->providerUser->id,
            'business_name' => 'Dalat Pine Express',
        ]);

        $this->trip = Trip::factory()->create([
            'owner_id' => $this->leader->id,
            'name' => 'Da Lat Getaway',
        ]);

        TripMember::create([
            'trip_id' => $this->trip->id,
            'user_id' => $this->leader->id,
            'role' => 'leader',
        ]);

        TripMember::create([
            'trip_id' => $this->trip->id,
            'user_id' => $this->editor->id,
            'role' => 'editor',
        ]);

        $this->booking = Booking::factory()->create([
            'trip_id' => $this->trip->id,
            'provider_id' => $this->provider->id,
            'item_name' => 'Airport Shuttle Service',
            'status' => 'confirmed',
        ]);
    }

    public function test_guest_cannot_access_trip_or_booking_chat(): void
    {
        $this->getJson(route('ajax.trips.messages', $this->trip))->assertStatus(401);
        $this->getJson(route('ajax.bookings.messages', $this->booking))->assertStatus(401);
    }

    public function test_trip_member_can_send_and_fetch_trip_group_messages(): void
    {
        // 1. Leader posts a message
        $sendRes = $this->actingAs($this->leader)->postJson(route('ajax.trips.messages.store', $this->trip), [
            'content' => 'Welcome to our Da Lat planning room!',
        ]);

        $sendRes->assertStatus(201);
        $sendRes->assertJsonPath('success', true);
        $sendRes->assertJsonPath('message.content', 'Welcome to our Da Lat planning room!');
        $sendRes->assertJsonPath('message.is_mine', true);

        $this->assertDatabaseHas('messages', [
            'trip_id' => $this->trip->id,
            'booking_id' => null,
            'sender_id' => $this->leader->id,
            'content' => 'Welcome to our Da Lat planning room!',
        ]);

        // 2. Editor fetches history
        $fetchRes = $this->actingAs($this->editor)->getJson(route('ajax.trips.messages', $this->trip));
        $fetchRes->assertStatus(200);
        $fetchRes->assertJsonCount(1, 'messages');
        $fetchRes->assertJsonPath('messages.0.content', 'Welcome to our Da Lat planning room!');
        $fetchRes->assertJsonPath('messages.0.is_mine', false);
    }

    public function test_outsider_cannot_read_or_post_in_trip_chat(): void
    {
        $this->actingAs($this->outsider)->getJson(route('ajax.trips.messages', $this->trip))->assertStatus(403);

        $this->actingAs($this->outsider)->postJson(route('ajax.trips.messages.store', $this->trip), [
            'content' => 'Intruder message',
        ])->assertStatus(403);
    }

    public function test_participant_can_send_direct_booking_message_and_trigger_notification(): void
    {
        // Traveler sends message to provider
        $response = $this->actingAs($this->leader)->postJson(route('ajax.bookings.messages.store', $this->booking), [
            'content' => 'Hello! We will arrive at Lien Khuong Airport at 11 AM.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('messages', [
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'sender_id' => $this->leader->id,
            'receiver_id' => $this->providerUser->id,
            'content' => 'Hello! We will arrive at Lien Khuong Airport at 11 AM.',
        ]);

        // In-app notification was sent to provider user
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->providerUser->id,
            'type' => NotificationService::TYPE_NEW_MESSAGE,
        ]);

        // Provider fetches messages and marks them as read
        $fetchRes = $this->actingAs($this->providerUser)->getJson(route('ajax.bookings.messages', $this->booking));
        $fetchRes->assertStatus(200);
        $fetchRes->assertJsonCount(1, 'messages');

        $message = Message::first();
        $this->assertTrue($message->fresh()->is_read);
    }

    public function test_outsider_cannot_access_booking_chat(): void
    {
        $this->actingAs($this->outsider)->getJson(route('ajax.bookings.messages', $this->booking))->assertStatus(403);

        $this->actingAs($this->outsider)->postJson(route('ajax.bookings.messages.store', $this->booking), [
            'content' => 'Unauthorized booking message',
        ])->assertStatus(403);
    }

    public function test_polling_endpoint_filters_by_after_id(): void
    {
        $msg1 = Message::factory()->create([
            'trip_id' => $this->trip->id,
            'sender_id' => $this->leader->id,
            'content' => 'First message',
        ]);

        $msg2 = Message::factory()->create([
            'trip_id' => $this->trip->id,
            'sender_id' => $this->editor->id,
            'content' => 'Second message',
        ]);

        $res = $this->actingAs($this->leader)->getJson(route('ajax.trips.messages', [
            'trip' => $this->trip,
            'after_id' => $msg1->id,
        ]));

        $res->assertStatus(200);
        $res->assertJsonCount(1, 'messages');
        $res->assertJsonPath('messages.0.id', $msg2->id);
        $res->assertJsonPath('messages.0.content', 'Second message');
    }

    public function test_chat_message_with_image_attachment(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('hotel_view.jpg', 800, 600);

        $response = $this->actingAs($this->leader)->postJson(route('ajax.trips.messages.store', $this->trip), [
            'content' => 'Look at this hotel view!',
            'attachment' => $image,
        ]);

        $response->assertStatus(201);

        $message = Message::first();
        $this->assertNotNull($message->attachment);
        Storage::disk('public')->assertExists($message->attachment);
        $this->assertNotNull($response->json('message.attachment_url'));
    }

    public function test_unread_count_endpoint_returns_accurate_direct_messages_count(): void
    {
        // 2 unread messages addressed to leader
        Message::factory()->create([
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'sender_id' => $this->providerUser->id,
            'receiver_id' => $this->leader->id,
            'is_read' => false,
        ]);

        Message::factory()->create([
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'sender_id' => $this->providerUser->id,
            'receiver_id' => $this->leader->id,
            'is_read' => false,
        ]);

        // 1 read message
        Message::factory()->create([
            'trip_id' => $this->trip->id,
            'booking_id' => $this->booking->id,
            'sender_id' => $this->providerUser->id,
            'receiver_id' => $this->leader->id,
            'is_read' => true,
        ]);

        $response = $this->actingAs($this->leader)->getJson(route('ajax.messages.unread-count'));
        $response->assertStatus(200);
        $response->assertJson(['unread' => 2]);
    }
}
