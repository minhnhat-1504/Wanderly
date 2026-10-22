<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_shared_trip_via_slug(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Sunny Phu Quoc Vacation',
            'share_slug' => 'phu-quoc-summer',
            'is_shared' => true,
            'is_share_budget_visible' => true,
        ]);

        $response = $this->get('/s/phu-quoc-summer');

        $response->assertStatus(200);
        $response->assertViewIs('public.share');
        $response->assertSee('Sunny Phu Quoc Vacation');
    }

    public function test_shared_trip_returns_404_when_is_shared_is_false(): void
    {
        $trip = Trip::factory()->create([
            'share_slug' => 'private-plan',
            'is_shared' => false,
        ]);

        $response = $this->get('/s/private-plan');

        $response->assertStatus(404);
    }

    public function test_shared_trip_returns_404_when_slug_does_not_exist(): void
    {
        $response = $this->get('/s/non-existent-random-slug');

        $response->assertStatus(404);
    }

    public function test_public_page_displays_budget_when_budget_visibility_is_enabled(): void
    {
        $trip = Trip::factory()->create([
            'budget' => 8000000,
            'share_slug' => 'open-budget-trip',
            'is_shared' => true,
            'is_share_budget_visible' => true,
        ]);

        $response = $this->get('/s/open-budget-trip');

        $response->assertStatus(200);
        $response->assertViewHas('budgetStatus', function ($status) {
            return $status !== null && $status['budget'] == 8000000;
        });
        $response->assertSee('Trip Budget Overview');
    }

    public function test_public_page_hides_budget_when_budget_visibility_is_disabled(): void
    {
        $trip = Trip::factory()->create([
            'budget' => 8000000,
            'share_slug' => 'hidden-budget-trip',
            'is_shared' => true,
            'is_share_budget_visible' => false,
        ]);

        $response = $this->get('/s/hidden-budget-trip');

        $response->assertStatus(200);
        $response->assertViewHas('budgetStatus', null);
        $response->assertSee('Budget and cost details are kept private by the organizer.');
    }

    public function test_owner_can_toggle_sharing_status(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'is_shared' => false,
            'share_slug' => null,
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        // 1. Enable sharing
        $response = $this->actingAs($owner)->postJson("/trips/{$trip->id}/share/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_shared' => true,
            ]);

        $trip->refresh();
        $this->assertTrue($trip->is_shared);
        $this->assertNotNull($trip->share_slug);

        // 2. Disable sharing
        $response = $this->actingAs($owner)->postJson("/trips/{$trip->id}/share/toggle");
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_shared' => false,
            ]);

        $trip->refresh();
        $this->assertFalse($trip->is_shared);
    }

    public function test_owner_can_toggle_budget_visibility(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'is_share_budget_visible' => true,
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)->postJson("/trips/{$trip->id}/share/budget");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'is_share_budget_visible' => false,
            ]);

        $trip->refresh();
        $this->assertFalse($trip->is_share_budget_visible);
    }

    public function test_owner_can_revoke_share_link(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'share_slug' => 'old-leaked-slug',
            'is_shared' => true,
        ]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)->postJson("/trips/{$trip->id}/share/revoke");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $trip->refresh();
        $this->assertNotEquals('old-leaked-slug', $trip->share_slug);

        // Old slug should now return 404
        $this->get('/s/old-leaked-slug')->assertStatus(404);

        // New slug should return 200
        $this->get("/s/{$trip->share_slug}")->assertStatus(200);
    }

    public function test_viewer_role_cannot_manage_share_settings(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $this->actingAs($viewer)->postJson("/trips/{$trip->id}/share/toggle")->assertStatus(403);
        $this->actingAs($viewer)->postJson("/trips/{$trip->id}/share/revoke")->assertStatus(403);
        $this->actingAs($viewer)->postJson("/trips/{$trip->id}/share/budget")->assertStatus(403);
    }
}
