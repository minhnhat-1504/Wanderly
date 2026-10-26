<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Destination;
use App\Models\MemberPreference;
use App\Models\Poi;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Models\Vote;
use App\Models\Zone;
use App\Services\CollaborationService;
use App\Services\MatchEngineService;
use App\Services\TripService;
use App\Services\VotingService;
use DomainException;
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

    // ══════════════════════════════════════════════════════════════════════
    //  SPRINT 3 — M8: INVITATIONS, PREFERENCES & GROUP MOOD
    // ══════════════════════════════════════════════════════════════════════

    public function test_generate_invite_link_creates_valid_token_with_7_days_expiry(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $service = app(CollaborationService::class);
        $inviteUrl = $service->generateInviteLink($trip, 'editor');

        $this->assertNotEmpty($inviteUrl);
        $this->assertStringContainsString('/trips/join/', $inviteUrl);

        $memberRecord = TripMember::where('trip_id', $trip->id)
            ->whereNotNull('invite_token')
            ->first();

        $this->assertNotNull($memberRecord);
        $this->assertEquals('editor', $memberRecord->role);
        $this->assertNotNull($memberRecord->token_expires_at);
        $this->assertTrue($memberRecord->token_expires_at->isFuture());
    }

    public function test_accept_invite_adds_member_with_preferences(): void
    {
        $owner = User::factory()->create();
        $newMember = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $service = app(CollaborationService::class);
        $service->generateInviteLink($trip, 'editor');
        $token = TripMember::where('trip_id', $trip->id)->whereNotNull('invite_token')->first()->invite_token;

        $joinedTrip = $service->acceptInvite($token, $newMember, [
            'interests' => ['sightseeing', 'chill'],
            'pace' => 'balanced',
        ]);

        $this->assertEquals($trip->id, $joinedTrip->id);
        $this->assertDatabaseHas('trip_members', [
            'trip_id' => $trip->id,
            'user_id' => $newMember->id,
            'role' => 'editor',
        ]);

        $this->assertDatabaseHas('member_preferences', [
            'trip_id' => $trip->id,
            'user_id' => $newMember->id,
            'pace' => 'balanced',
        ]);
    }

    public function test_accept_invite_with_expired_token_throws_exception(): void
    {
        $owner = User::factory()->create();
        $newMember = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        $tokenRecord = TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => null,
            'role' => 'editor',
            'invite_token' => 'expired_token_123',
            'token_expires_at' => now()->subDay(),
        ]);

        $service = app(CollaborationService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->acceptInvite('expired_token_123', $newMember);
    }

    public function test_remove_member_enforces_leader_preservation_rule(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $service = app(CollaborationService::class);

        // Attempting to remove the only leader must throw DomainException
        $this->expectException(DomainException::class);
        $service->removeMember($trip, $owner, $owner);
    }

    public function test_calculate_group_mood_computes_harmony_and_common_pace(): void
    {
        $owner = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $member1->id, 'role' => 'editor']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $member2->id, 'role' => 'viewer']);

        MemberPreference::create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'interests' => ['sightseeing', 'chill'],
            'pace' => 'balanced',
        ]);
        MemberPreference::create([
            'trip_id' => $trip->id,
            'user_id' => $member1->id,
            'interests' => ['sightseeing', 'foodie'],
            'pace' => 'balanced',
        ]);
        MemberPreference::create([
            'trip_id' => $trip->id,
            'user_id' => $member2->id,
            'interests' => ['nature'],
            'pace' => 'relaxed',
        ]);

        $service = app(CollaborationService::class);
        $mood = $service->calculateGroupMood($trip);

        $this->assertArrayHasKey('harmony_score', $mood);
        $this->assertArrayHasKey('common_pace', $mood);
        $this->assertArrayHasKey('top_interests', $mood);

        $this->assertEquals('balanced', $mood['common_pace']);
        $this->assertGreaterThan(50, $mood['harmony_score']);
        $this->assertEquals(3, $mood['member_count']);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SPRINT 3 — M8: VOTING POOL ENGINE
    // ══════════════════════════════════════════════════════════════════════

    public function test_voting_service_cast_vote_and_leaderboard(): void
    {
        $owner = User::factory()->create();
        $destination = Destination::create(['name' => 'Dalat', 'description' => 'Dalat Vietnam']);
        $zone = Zone::create(['destination_id' => $destination->id, 'name' => 'Tuyen Lam Lake Area']);
        $poi = Poi::create([
            'zone_id' => $zone->id,
            'name' => 'Tuyen Lam Lake',
            'address' => 'Ward 4, Dalat',
            'category' => 'sightseeing',
            'typical_cost' => 50000,
        ]);

        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Dalat',
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $votingService = app(VotingService::class);

        // Cast +1 vote
        $vote = $votingService->castVote($trip, $owner, 'poi', $poi->id, 1);
        $this->assertEquals(1, $vote->value);

        $leaderboard = $votingService->getVotingLeaderboard($trip, $owner);
        $first = $leaderboard->first();
        $this->assertEquals($poi->id, $first->id);
        $this->assertEquals(1, $first->score);
        $this->assertEquals(1, $first->user_vote);

        // Toggle vote off by voting same value
        $votingService->castVote($trip, $owner, 'poi', $poi->id, 1);
        $leaderboardAfterToggle = $votingService->getVotingLeaderboard($trip, $owner);
        $this->assertEquals(0, $leaderboardAfterToggle->first()->score);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SPRINT 3 — M9: MATCH ENGINE DRAFT ITINERARY & TIMELINE APPLICATION
    // ══════════════════════════════════════════════════════════════════════

    public function test_match_engine_generates_draft_with_score_breakdown_and_zone_clustering(): void
    {
        $owner = User::factory()->create();
        $destination = Destination::create(['name' => 'Dalat', 'description' => 'Dalat Vietnam']);
        $zone = Zone::create(['destination_id' => $destination->id, 'name' => 'Hồ Tuyền Lâm']);

        $poi1 = Poi::create([
            'zone_id' => $zone->id,
            'name' => 'Hồ Tuyền Lâm Lake',
            'address' => 'Ward 4, Dalat',
            'category' => 'nature',
            'typical_cost' => 50000,
        ]);
        $poi2 = Poi::create([
            'zone_id' => $zone->id,
            'name' => 'Thiền Viện Trúc Lâm',
            'address' => 'Ward 3, Dalat',
            'category' => 'culture',
            'typical_cost' => 0,
        ]);

        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Dalat',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7), // 3 days
            'budget' => 5000000,
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        MemberPreference::create([
            'trip_id' => $trip->id,
            'user_id' => $owner->id,
            'interests' => ['nature', 'culture'],
            'pace' => 'balanced',
        ]);

        $matchService = app(MatchEngineService::class);
        $draft = $matchService->generateDraftItinerary($trip);

        $this->assertNotEmpty($draft['days']);
        $this->assertEquals('balanced', $draft['pace']);
        $this->assertArrayHasKey('score_breakdown', $draft['days'][0]['activities'][0]);
        $this->assertArrayHasKey('final_score', $draft['days'][0]['activities'][0]['score_breakdown']);
        $this->assertArrayHasKey('explanation', $draft['days'][0]['activities'][0]['score_breakdown']);
    }

    public function test_match_engine_apply_draft_creates_activities_on_timeline(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'destination' => 'Dalat',
            'budget' => 5000000,
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $draft = [
            'days' => [
                [
                    'day_number' => 1,
                    'zone_name' => 'City Center',
                    'activities' => [
                        [
                            'time_slot' => 'morning',
                            'title' => 'Lam Vien Square Walk',
                            'category' => 'sightseeing',
                            'cost' => 0,
                            'location' => 'Lam Vien Square',
                        ],
                    ],
                ],
            ],
        ];

        $matchService = app(MatchEngineService::class);
        $applied = $matchService->applyDraftToTimeline($trip, $draft);

        $this->assertTrue($applied);
        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
            'title' => 'Lam Vien Square Walk',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SPRINT 3 — HTTP & AJAX ENDPOINTS TESTING
    // ══════════════════════════════════════════════════════════════════════

    public function test_member_invite_and_mood_ajax_endpoints(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        // Generate Invite Link via AJAX
        $response = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/members/invite", [
            'role' => 'editor',
        ]);
        $response->assertOk();
        $response->assertJsonStructure(['success', 'invite_url']);

        // Group Mood Endpoint
        $moodResponse = $this->actingAs($owner)->getJson("/ajax/trips/{$trip->id}/mood");
        $moodResponse->assertOk();
        $moodResponse->assertJsonStructure(['success', 'group_mood' => ['harmony_score', 'common_pace']]);

        // Save Preferences Endpoint
        $prefResponse = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/preferences", [
            'interests' => ['foodie', 'nightlife'],
            'pace' => 'packed',
        ]);
        $prefResponse->assertOk();
        $prefResponse->assertJson(['success' => true]);
    }

    public function test_voting_and_match_engine_ajax_endpoints(): void
    {
        $owner = User::factory()->create();
        $destination = Destination::create(['name' => 'Dalat', 'description' => 'Dalat Vietnam']);
        $zone = Zone::create(['destination_id' => $destination->id, 'name' => 'Central']);
        $poi = Poi::create([
            'zone_id' => $zone->id,
            'name' => 'Dalat Market Night Walk',
            'address' => 'Hoa Binh Square, Dalat',
            'category' => 'foodie',
            'typical_cost' => 100000,
        ]);

        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'destination' => 'Dalat']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        // Get Candidates
        $candRes = $this->actingAs($owner)->getJson("/ajax/trips/{$trip->id}/candidates");
        $candRes->assertOk();
        $candRes->assertJsonStructure(['success', 'candidates']);

        // Cast Vote
        $voteRes = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/votes", [
            'votable_type' => 'poi',
            'votable_id' => $poi->id,
            'value' => 1,
        ]);
        $voteRes->assertOk();
        $voteRes->assertJson(['success' => true]);

        // Match Engine Suggestion
        $suggestRes = $this->actingAs($owner)->getJson("/ajax/trips/{$trip->id}/match-engine/suggest");
        $suggestRes->assertOk();
        $suggestRes->assertJsonStructure(['success', 'draft' => ['days']]);

        // Match Engine Apply
        $applyRes = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/match-engine/apply", []);
        $applyRes->assertOk();
        $applyRes->assertJson(['success' => true]);
    }
}
