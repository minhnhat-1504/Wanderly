<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_trips_index(): void
    {
        $response = $this->get(route('trips.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_trips_create(): void
    {
        $response = $this->get(route('trips.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_trips_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('trips.index'));

        $response->assertStatus(200);
        $response->assertViewIs('trips.index');
        $response->assertViewHas('trips');
    }

    public function test_validation_fails_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('trips.store'), []);

        $response->assertSessionHasErrors([
            'name',
            'destination',
            'start_date',
            'end_date',
            'budget',
            'privacy',
        ]);
    }

    public function test_validation_fails_when_end_date_is_before_start_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('trips.store'), [
            'name' => 'Invalid Dates Trip',
            'destination' => 'Da Lat',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-05',
            'budget' => 5000000,
            'privacy' => 'private',
        ]);

        $response->assertSessionHasErrors(['end_date']);
    }

    public function test_authenticated_user_can_create_trip_and_is_assigned_as_leader(): void
    {
        $user = User::factory()->create();

        $tripData = [
            'name' => 'Explore Da Lat Pine Forests',
            'destination' => 'Da Lat',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'budget' => 6000000,
            'privacy' => 'private',
        ];

        $response = $this->actingAs($user)->post(route('trips.store'), $tripData);

        $this->assertDatabaseHas('trips', [
            'owner_id' => $user->id,
            'name' => 'Explore Da Lat Pine Forests',
            'destination' => 'Da Lat',
            'budget' => '6000000.00',
            'privacy' => 'private',
            'status' => 'planning',
        ]);

        $trip = Trip::where('name', 'Explore Da Lat Pine Forests')->first();

        // Verify that the creator is automatically recorded in trip_members with leader role
        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'role' => 'leader',
        ]);

        $response->assertRedirect(route('trips.show', $trip));
        $response->assertSessionHas('success');
    }

    public function test_user_cannot_view_another_users_private_trip(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $trip = Trip::create([
            'owner_id' => $owner->id,
            'name' => 'Secret Trip',
            'destination' => 'Da Nang',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-04',
            'budget' => 3000000,
            'privacy' => 'private',
            'status' => 'planning',
        ]);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        $response = $this->actingAs($stranger)->get(route('trips.show', $trip));

        $response->assertStatus(403);
    }

    public function test_user_can_view_public_trip(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $trip = Trip::create([
            'owner_id' => $owner->id,
            'name' => 'Community Trip',
            'destination' => 'Phu Quoc',
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-15',
            'budget' => 8000000,
            'privacy' => 'public',
            'status' => 'planning',
        ]);

        $response = $this->actingAs($stranger)->get(route('trips.show', $trip));

        $response->assertStatus(200);
        $response->assertSee('Community Trip');
    }

    public function test_only_leader_or_editor_can_update_trip(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();

        $trip = Trip::create([
            'owner_id' => $owner->id,
            'name' => 'Initial Trip',
            'destination' => 'Hoi An',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'budget' => 4000000,
            'privacy' => 'group',
            'status' => 'planning',
        ]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $editor->id, 'role' => 'editor']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $updateData = [
            'name' => 'Updated by Editor',
            'destination' => 'Hoi An Ancient Town',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-04',
            'budget' => 4500000,
            'privacy' => 'group',
            'status' => 'planning',
        ];

        // Viewer should be forbidden (403)
        $viewerResponse = $this->actingAs($viewer)->put(route('trips.update', $trip), $updateData);
        $viewerResponse->assertStatus(403);

        // Editor should succeed
        $editorResponse = $this->actingAs($editor)->put(route('trips.update', $trip), $updateData);
        $editorResponse->assertRedirect(route('trips.show', $trip));
        $this->assertDatabaseHas('trips', ['id' => $trip->id, 'name' => 'Updated by Editor']);
    }

    public function test_only_leader_can_delete_trip(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();

        $trip = Trip::create([
            'owner_id' => $owner->id,
            'name' => 'Trip To Be Deleted',
            'destination' => 'Sa Pa',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
            'budget' => 5000000,
            'privacy' => 'private',
            'status' => 'planning',
        ]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $editor->id, 'role' => 'editor']);

        // Editor cannot delete (403)
        $editorResponse = $this->actingAs($editor)->delete(route('trips.destroy', $trip));
        $editorResponse->assertStatus(403);
        $this->assertDatabaseHas('trips', ['id' => $trip->id]);

        // Leader can delete
        $leaderResponse = $this->actingAs($owner)->delete(route('trips.destroy', $trip));
        $leaderResponse->assertRedirect(route('trips.index'));
        $this->assertDatabaseMissing('trips', ['id' => $trip->id]);
        $this->assertDatabaseMissing('trip_members', ['trip_id' => $trip->id]);
    }

    public function test_trip_service_calculates_trip_days_correctly(): void
    {
        $tripService = new TripService;

        $trip = new Trip([
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
        ]);

        $days = $tripService->calculateTripDays($trip);

        $this->assertEquals(5, $days);
    }

    public function test_dashboard_displays_correct_trip_spent_and_budget_percentage(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'budget' => 10000000,
            'privacy' => 'public',
        ]);
        TripMember::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'role' => 'leader',
        ]);

        // Create activities totaling 6,200,000 (62% of 10,000,000)
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'cost' => 3000000,
            'start_time' => '07:00',
            'end_time' => '09:00',
        ]);
        Activity::factory()->create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'afternoon',
            'cost' => 3200000,
            'start_time' => '14:00',
            'end_time' => '16:00',
        ]);

        $this->assertEquals(6200000, $trip->spent);

        $response = $this->actingAs($owner)->get(route('trips.index'));
        $response->assertStatus(200);

        // Assert 62% used and spent value rendered on dashboard card
        $response->assertSee('Used: 62%', false);
        $response->assertSee('6.200.000 VND', false);
    }
}
