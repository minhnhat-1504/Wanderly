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

        // Viewer loading trip show page gets view-only mode
        $pageResponse = $this->actingAs($viewer)->get(route('trips.show', $trip));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('data-can-edit="false"', false);
        $pageResponse->assertSee('View Only');
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

    public function test_activity_creation_validates_timeslot_boundaries(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        // Attempt morning activity outside 06:00 - 11:00 bounds (05:00 - 07:00)
        $resEarly = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Too Early Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 10000,
            'category' => 'sightseeing',
            'location' => 'Location',
            'start_time' => '05:00',
            'end_time' => '07:00',
        ]);
        $resEarly->assertStatus(422);
        $resEarly->assertJsonValidationErrors(['start_time']);

        // Attempt morning activity extending past 11:00 (10:00 - 12:00)
        $resLate = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Too Late Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 10000,
            'category' => 'sightseeing',
            'location' => 'Location',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
        $resLate->assertStatus(422);
        $resLate->assertJsonValidationErrors(['start_time']);
    }

    public function test_activity_creation_requires_start_time_before_end_time(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $response = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Backwards Time Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 10000,
            'category' => 'sightseeing',
            'location' => 'Location',
            'start_time' => '10:00',
            'end_time' => '08:00',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_time']);
    }

    public function test_activity_creation_rejects_overlapping_time_on_same_day(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        // Existing activity: 08:00 - 09:30
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'start_time' => '08:00',
            'end_time' => '09:30',
        ]);

        // Attempt overlapping activity: 09:00 - 10:30
        $response = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Conflicting Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 20000,
            'category' => 'cafe',
            'location' => 'Location',
            'start_time' => '09:00',
            'end_time' => '10:30',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['start_time']);

        // Adjacent activity (09:30 - 10:30) must succeed without error
        $okResponse = $this->actingAs($owner)->postJson(route('ajax.trips.activities.store', $trip), [
            'title' => 'Adjacent Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 20000,
            'category' => 'cafe',
            'location' => 'Location',
            'start_time' => '09:30',
            'end_time' => '10:30',
        ]);
        $okResponse->assertStatus(201);
    }

    public function test_multiple_activities_in_timeslot_ordered_chronologically(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $service = app(ItineraryService::class);

        // Create later activity first: 09:30 - 10:30
        $later = $service->createActivity($trip, [
            'title' => 'Later Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'start_time' => '09:30',
            'end_time' => '10:30',
            'cost' => 10000,
            'category' => 'cafe',
            'location' => 'Location B',
        ]);

        // Create earlier activity second: 07:00 - 08:30
        $earlier = $service->createActivity($trip, [
            'title' => 'Earlier Activity',
            'day' => 1,
            'time_slot' => 'morning',
            'start_time' => '07:00',
            'end_time' => '08:30',
            'cost' => 15000,
            'category' => 'sightseeing',
            'location' => 'Location A',
        ]);

        $timeline = $service->getTripTimeline($trip);
        $morningActivities = $timeline[1]['morning'];

        $this->assertCount(2, $morningActivities);
        $this->assertEquals($earlier->id, $morningActivities[0]->id, 'Earlier activity must be placed first');
        $this->assertEquals($later->id, $morningActivities[1]->id, 'Later activity must be placed second');
    }

    public function test_trip_show_renders_toggle_for_more_than_two_activities(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
        ]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        // Create 2 activities: no toggle button should be displayed
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Morning 1',
            'start_time' => '07:00',
            'end_time' => '08:00',
            'position' => 0,
        ]);
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Morning 2',
            'start_time' => '08:30',
            'end_time' => '09:30',
            'position' => 1,
        ]);

        $resTwo = $this->actingAs($owner)->get(route('trips.show', $trip));
        $resTwo->assertStatus(200);
        $resTwo->assertDontSee('Show all (', false);

        // Add 3rd activity: toggle button must now appear with 3 activities count
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Morning 3',
            'start_time' => '09:45',
            'end_time' => '10:45',
            'position' => 2,
        ]);

        $resThree = $this->actingAs($owner)->get(route('trips.show', $trip));
        $resThree->assertStatus(200);
        $resThree->assertSee('Show all (3 activities)', false);
        $resThree->assertSee('Hide (Show 2 only)', false);
    }
}
