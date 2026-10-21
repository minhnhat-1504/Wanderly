<?php

namespace Tests\Feature;

use App\Events\RoomBudgetToggled;
use App\Models\RoomBooking;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\BudgetService;
use App\Services\RoomBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    protected BudgetService $budgetService;

    protected RoomBookingService $roomService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetService = app(BudgetService::class);
        $this->roomService = app(RoomBookingService::class);
    }

    public function test_budget_service_calculates_budget_status_correctly_with_room_bookings(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 10000000.00,
        ]);

        RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 3000000.00,
            'is_budget_included' => true,
        ]);

        RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 2000000.00,
            'is_budget_included' => false,
        ]);

        $status = $this->budgetService->calculateBudgetStatus($trip);

        $this->assertEquals(10000000.00, $status['budget']);
        $this->assertEquals(3000000.00, $status['room_cost']);
        $this->assertEquals(3000000.00, $status['total_spent']);
        $this->assertEquals(7000000.00, $status['remaining_budget']);
        $this->assertEquals(30.00, $status['percentage']);
        $this->assertEquals('normal', $status['status']);
    }

    public function test_budget_service_detects_warning_threshold_at_80_percent(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 10000000.00,
        ]);

        RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 8200000.00,
            'is_budget_included' => true,
        ]);

        $status = $this->budgetService->calculateBudgetStatus($trip);

        $this->assertEquals(82.00, $status['percentage']);
        $this->assertEquals('warning', $status['status']);
        $this->assertEquals('warning', $this->budgetService->checkThreshold($trip, 8200000.00));
    }

    public function test_budget_service_detects_danger_threshold_above_95_percent(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 10000000.00,
        ]);

        RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 9600000.00,
            'is_budget_included' => true,
        ]);

        $status = $this->budgetService->calculateBudgetStatus($trip);

        $this->assertEquals(96.00, $status['percentage']);
        $this->assertEquals('danger', $status['status']);
        $this->assertEquals('danger', $this->budgetService->checkThreshold($trip, 9600000.00));
    }

    public function test_owner_and_leader_and_editor_can_view_budget(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();

        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 5000000.00,
        ]);

        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $editor->id,
            'role' => 'editor',
        ]);

        // Owner can view
        $response = $this->actingAs($owner)->getJson("/trips/{$trip->id}/budget");
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.budget', fn ($val) => (float) $val === 5000000.0);

        // Editor can view
        $response = $this->actingAs($editor)->getJson("/trips/{$trip->id}/budget");
        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_viewer_role_cannot_view_budget_returns_403(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 5000000.00,
        ]);

        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewer)->getJson("/trips/{$trip->id}/budget");
        $response->assertForbidden();
    }

    public function test_guest_cannot_view_budget_redirects_to_login(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        $response = $this->get("/trips/{$trip->id}/budget");
        $response->assertRedirect('/login');
    }

    public function test_authorized_user_can_toggle_room_budget_inclusion(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 5000000.00,
        ]);

        $room = RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 1500000.00,
            'is_budget_included' => true,
        ]);

        $response = $this->actingAs($owner)->patchJson("/ajax/rooms/{$room->id}/toggle-budget");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_budget_included', false)
            ->assertJsonPath('budget.total_spent', fn ($val) => (float) $val === 0.0);

        $this->assertDatabaseHas('room_bookings', [
            'id' => $room->id,
            'is_budget_included' => false,
        ]);

        // Toggle back to true
        $response = $this->actingAs($owner)->patchJson("/ajax/rooms/{$room->id}/toggle-budget");
        $response->assertOk()
            ->assertJsonPath('is_budget_included', true)
            ->assertJsonPath('budget.total_spent', fn ($val) => (float) $val === 1500000.0);
    }

    public function test_viewer_cannot_toggle_room_budget_returns_403(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        $room = RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'price' => 1000000.00,
            'is_budget_included' => true,
        ]);

        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewer)->patchJson("/ajax/rooms/{$room->id}/toggle-budget");
        $response->assertForbidden();
    }

    public function test_leader_can_store_update_and_delete_room_booking(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        // Store
        $postData = [
            'hotel_name' => 'Ana Mandara Villas Dalat',
            'room_type' => 'Villa Suite',
            'check_in' => now()->addDays(2)->format('Y-m-d'),
            'check_out' => now()->addDays(5)->format('Y-m-d'),
            'price' => 3500000.00,
            'address' => 'Le Lai Street, Ward 5, Da Lat',
            'notes' => 'Quiet villa near pine hill',
            'is_budget_included' => true,
        ];

        $response = $this->actingAs($owner)->postJson("/trips/{$trip->id}/rooms", $postData);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hotel_name', 'Ana Mandara Villas Dalat');

        $this->assertDatabaseHas('room_bookings', [
            'trip_id' => $trip->id,
            'hotel_name' => 'Ana Mandara Villas Dalat',
        ]);

        $roomId = $response->json('data.id');

        // Update
        $updateData = [
            'hotel_name' => 'Ana Mandara Villas Dalat Resort',
            'price' => 4000000.00,
        ];

        $response = $this->actingAs($owner)->putJson("/rooms/{$roomId}", $updateData);
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hotel_name', 'Ana Mandara Villas Dalat Resort')
            ->assertJsonPath('data.price', '4000000.00');

        $this->assertDatabaseHas('room_bookings', [
            'id' => $roomId,
            'hotel_name' => 'Ana Mandara Villas Dalat Resort',
        ]);

        // Destroy
        $response = $this->actingAs($owner)->deleteJson("/rooms/{$roomId}");
        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('room_bookings', [
            'id' => $roomId,
        ]);
    }

    public function test_room_budget_toggled_dispatches_event(): void
    {
        Event::fake([RoomBudgetToggled::class]);

        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        $room = RoomBooking::factory()->create([
            'trip_id' => $trip->id,
            'is_budget_included' => true,
        ]);

        $this->roomService->toggleBudgetInclusion($room);

        Event::assertDispatched(RoomBudgetToggled::class, function ($event) use ($room) {
            return $event->roomBooking->id === $room->id;
        });
    }
}
