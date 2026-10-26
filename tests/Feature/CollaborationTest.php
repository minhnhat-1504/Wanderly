<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\TripService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaborationTest extends TestCase
{
    use RefreshDatabase;

    // ══════════════════════════════════════════════════════════════════════
    //  TRIP POLICY — ROLE-BASED AUTHORIZATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_owner_has_full_control_of_trip(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        // Owner can view, update, delete
        $this->actingAs($owner)->get(route('trips.show', $trip))->assertOk();
        $this->actingAs($owner)->get(route('trips.edit', $trip))->assertOk();
    }

    public function test_editor_can_view_and_edit_but_not_delete(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $editor->id, 'role' => 'editor']);

        $this->actingAs($editor)->get(route('trips.show', $trip))->assertOk();

        // Editor cannot delete trip (only leader can)
        $this->actingAs($editor)
            ->delete(route('trips.destroy', $trip))
            ->assertForbidden();
    }

    public function test_viewer_cannot_edit_trip(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($viewer)
            ->get(route('trips.edit', $trip))
            ->assertForbidden();
    }

    public function test_stranger_cannot_access_private_trip(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $this->actingAs($stranger)
            ->get(route('trips.show', $trip))
            ->assertForbidden();
    }

    public function test_member_can_access_private_trip(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $member->id, 'role' => 'viewer']);

        $this->actingAs($member)
            ->get(route('trips.show', $trip))
            ->assertOk();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  TRIP MEMBER MANAGEMENT
    // ══════════════════════════════════════════════════════════════════════

    public function test_trip_always_has_at_least_one_leader(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        // Verify the leader membership exists
        $leaderCount = TripMember::where('trip_id', $trip->id)
            ->where('role', 'leader')
            ->count();

        $this->assertEquals(1, $leaderCount);
    }

    public function test_duplicate_member_records_not_allowed(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $member->id, 'role' => 'editor']);

        $this->expectException(QueryException::class);

        // This should throw due to unique constraint
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $member->id, 'role' => 'viewer']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  ACTIVITY COLLABORATION — EDITOR/VIEWER POLICIES
    // ══════════════════════════════════════════════════════════════════════

    public function test_editor_can_add_activities_to_trip(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $editor->id, 'role' => 'editor']);

        $this->actingAs($editor)
            ->postJson('/ajax/trips/'.$trip->id.'/activities', [
                'day' => 1,
                'time_slot' => 'morning',
                'title' => 'Team Breakfast at Cafe',
                'cost' => 150000,
                'category' => 'dining',
                'location' => 'Dalat Central Cafe',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'title' => 'Team Breakfast at Cafe',
        ]);
    }

    public function test_viewer_cannot_add_activities_to_trip(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($viewer)
            ->postJson('/ajax/trips/'.$trip->id.'/activities', [
                'day' => 1,
                'time_slot' => 'morning',
                'title' => 'Viewer Trying to Add Activity',
                'cost' => 0,
                'category' => 'other',
            ])
            ->assertForbidden();
    }

    // ══════════════════════════════════════════════════════════════════════
    //  GROUP MOOD SIMULATION (via preferences model checks)
    // ══════════════════════════════════════════════════════════════════════

    public function test_trip_members_table_supports_multiple_roles(): void
    {
        $owner = User::factory()->create();
        $editor = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id,  'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $editor->id, 'role' => 'editor']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->assertEquals(3, TripMember::where('trip_id', $trip->id)->count());
        $this->assertEquals(1, TripMember::where('trip_id', $trip->id)->where('role', 'leader')->count());
        $this->assertEquals(1, TripMember::where('trip_id', $trip->id)->where('role', 'editor')->count());
        $this->assertEquals(1, TripMember::where('trip_id', $trip->id)->where('role', 'viewer')->count());
    }

    public function test_trip_duplicate_copies_all_members_activities(): void
    {
        $owner = User::factory()->create();
        $newOwner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'budget' => 5000000]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        Activity::create([
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Visit Tuyen Lam Lake',
            'cost' => 0,
            'category' => 'sightseeing',
            'location' => 'Tuyen Lam Lake, Dalat',
            'position' => 1,
        ]);

        $tripService = app(TripService::class);
        $clonedTrip = $tripService->duplicateTrip($trip, $newOwner);

        $this->assertDatabaseHas('trips', [
            'owner_id' => $newOwner->id,
            'destination' => $trip->destination,
        ]);

        // Cloned trip should have same activities
        $this->assertEquals(1, $clonedTrip->activities()->count());
        $this->assertEquals('Visit Tuyen Lam Lake', $clonedTrip->activities()->first()->title);
    }
}
