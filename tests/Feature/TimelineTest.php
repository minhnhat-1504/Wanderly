<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\ItineraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_activities(): void
    {
        $trip = Trip::factory()->create();

        $response = $this->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Morning Coffee',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 50000,
            'category' => 'cafe',
            'location' => 'Da Lat City',
        ]);

        $response->assertStatus(401);
    }

    public function test_viewer_role_cannot_create_or_modify_activities(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();

        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewer)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Trekking Lang Biang',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 120000,
            'category' => 'sightseeing',
            'location' => 'Lang Biang Peak',
        ]);

        $response->assertStatus(403);
    }

    public function test_leader_can_create_activity(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $response = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Túi Mơ To Cafe',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 65000,
            'category' => 'cafe',
            'location' => 'Sào Nam, Đà Lạt',
            'start_time' => '08:30',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'daily_cost' => 65000,
            'has_conflict' => false,
        ]);

        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'title' => 'Túi Mơ To Cafe',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => '65000.00',
        ]);
    }

    public function test_leader_can_update_activity(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $activity = Activity::factory()->create([
            'trip_id' => $trip->id,
            'title' => 'Original Title',
            'cost' => 50000,
            'day' => 1,
            'time_slot' => 'morning',
        ]);

        $response = $this->actingAs($owner)->putJson(route('ajax.trips.activities.update', [$trip, $activity]), [
            'title' => 'Updated Title',
            'cost' => 80000,
            'day' => 1,
            'time_slot' => 'midday',
            'category' => 'dining',
            'location' => 'Updated Location',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'daily_cost' => 80000,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'title' => 'Updated Title',
            'time_slot' => 'midday',
            'cost' => '80000.00',
        ]);
    }

    public function test_leader_can_delete_activity(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $activity = Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 2,
            'cost' => 150000,
        ]);

        $response = $this->actingAs($owner)->deleteJson(route('ajax.trips.activities.destroy', [$trip, $activity]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'daily_cost' => 0,
        ]);

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    public function test_reorder_activities_updates_day_slot_and_positions_atomically(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $act1 = Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'position' => 1,
        ]);

        $act2 = Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'position' => 2,
        ]);

        $response = $this->actingAs($owner)->putJson(route('ajax.trips.activities.reorder', $trip), [
            'items' => [
                [
                    'id' => $act1->id,
                    'day' => 2,
                    'time_slot' => 'evening',
                    'position' => 0,
                ],
                [
                    'id' => $act2->id,
                    'day' => 2,
                    'time_slot' => 'evening',
                    'position' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('activities', [
            'id' => $act1->id,
            'day' => 2,
            'time_slot' => 'evening',
            'position' => 0,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $act2->id,
            'day' => 2,
            'time_slot' => 'evening',
            'position' => 1,
        ]);
    }

    public function test_reorder_fails_validation_with_invalid_data(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->putJson(route('ajax.trips.activities.reorder', $trip), [
            'items' => [
                [
                    'id' => 999999, // Non-existent
                    'day' => 0,     // Invalid day
                    'time_slot' => 'midnight', // Invalid slot
                    'position' => -1, // Invalid position
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.id', 'items.0.day', 'items.0.time_slot', 'items.0.position']);
    }

    public function test_time_conflict_detection(): void
    {
        $trip = Trip::factory()->create([
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);

        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $itineraryService = app(ItineraryService::class);

        // Overlapping slot: 10:00 to 12:00 -> Conflict
        $hasConflict = $itineraryService->detectTimeConflict($trip, 1, '10:00', '12:00');
        $this->assertTrue($hasConflict);

        // Non-overlapping slot: 11:00 to 12:00 -> No conflict
        $hasNoConflict = $itineraryService->detectTimeConflict($trip, 1, '11:00', '12:00');
        $this->assertFalse($hasNoConflict);

        // Different day slot: 09:30 to 10:30 on Day 2 -> No conflict
        $differentDay = $itineraryService->detectTimeConflict($trip, 2, '09:30', '10:30');
        $this->assertFalse($differentDay);
    }

    public function test_user_can_duplicate_trip_with_all_activities(): void
    {
        $originalOwner = User::factory()->create();
        $newOwner = User::factory()->create();

        $originalTrip = Trip::factory()->create([
            'owner_id' => $originalOwner->id,
            'name' => 'Da Lat Adventure',
            'privacy' => 'public',
        ]);

        Activity::factory()->count(3)->create([
            'trip_id' => $originalTrip->id,
            'day' => 1,
        ]);

        $response = $this->actingAs($newOwner)->post(route('trips.duplicate', $originalTrip));

        $duplicatedTrip = Trip::where('owner_id', $newOwner->id)->first();
        $this->assertNotNull($duplicatedTrip);
        $response->assertRedirect(route('trips.show', $duplicatedTrip));

        $this->assertStringContainsString('Copy of Da Lat Adventure', $duplicatedTrip->name);
        $this->assertEquals(3, $duplicatedTrip->activities()->count());
        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $duplicatedTrip->id,
            'user_id' => $newOwner->id,
            'role' => 'leader',
        ]);
    }
}
