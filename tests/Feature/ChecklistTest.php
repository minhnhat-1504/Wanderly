<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\ChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_or_modify_checklist(): void
    {
        $trip = Trip::factory()->create();

        $this->getJson("/ajax/trips/{$trip->id}/checklist")->assertStatus(401);
        $this->postJson("/ajax/trips/{$trip->id}/checklist", ['title' => 'Warm Jacket'])->assertStatus(401);
    }

    public function test_viewer_role_cannot_modify_checklist(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $viewer->id, 'role' => 'viewer']);

        $response = $this->actingAs($viewer)->postJson("/ajax/trips/{$trip->id}/checklist", [
            'title' => 'Hacking item',
            'category' => 'gear',
        ]);

        $response->assertStatus(403);
    }

    public function test_leader_and_editor_can_add_checklist_item(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/checklist", [
            'title' => 'Hiking Shoes',
            'category' => 'clothing',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'item' => [
                    'title' => 'Hiking Shoes',
                    'category' => 'clothing',
                    'is_completed' => false,
                ],
                'progress' => [
                    'total' => 1,
                    'completed' => 0,
                    'percentage' => 0,
                ],
            ]);

        $this->assertDatabaseHas('checklist_items', [
            'trip_id' => $trip->id,
            'title' => 'Hiking Shoes',
        ]);
    }

    public function test_user_can_toggle_checklist_item_and_updates_progress(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $item1 = ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Passport',
            'category' => 'documents',
            'is_completed' => false,
            'position' => 1,
        ]);

        $item2 = ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Tickets',
            'category' => 'documents',
            'is_completed' => false,
            'position' => 2,
        ]);

        // Toggle item 1 -> should become 50%
        $response = $this->actingAs($owner)->patchJson("/ajax/trips/{$trip->id}/checklist/{$item1->id}/toggle");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'item' => [
                    'id' => $item1->id,
                    'is_completed' => true,
                ],
                'progress' => [
                    'total' => 2,
                    'completed' => 1,
                    'percentage' => 50,
                ],
            ]);

        $this->assertDatabaseHas('checklist_items', [
            'id' => $item1->id,
            'is_completed' => true,
        ]);
    }

    public function test_user_can_delete_checklist_item(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $item = ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Sun Hat',
            'category' => 'clothing',
            'is_completed' => true,
            'position' => 1,
        ]);

        $response = $this->actingAs($owner)->deleteJson("/ajax/trips/{$trip->id}/checklist/{$item->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'progress' => [
                    'total' => 0,
                    'completed' => 0,
                    'percentage' => 0,
                ],
            ]);

        $this->assertDatabaseMissing('checklist_items', ['id' => $item->id]);
    }

    public function test_user_can_apply_preset_checklist_template(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);
        TripMember::create(['trip_id' => $trip->id, 'user_id' => $owner->id, 'role' => 'leader']);

        $response = $this->actingAs($owner)->postJson("/ajax/trips/{$trip->id}/checklist/apply-template", [
            'template' => 'mountain',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('checklist_items', [
            'trip_id' => $trip->id,
            'title' => 'Warm Windbreaker Jacket',
        ]);

        $this->assertEquals(8, $trip->checklistItems()->count());
    }

    public function test_checklist_service_calculates_progress_correctly(): void
    {
        $trip = Trip::factory()->create();
        $service = new ChecklistService;

        // Empty trip -> 0%
        $this->assertEquals(0, $service->calculateProgress($trip)['percentage']);

        ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Item 1',
            'is_completed' => true,
            'position' => 1,
        ]);

        ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'Item 2',
            'is_completed' => false,
            'position' => 2,
        ]);

        $progress = $service->calculateProgress($trip);
        $this->assertEquals(2, $progress['total']);
        $this->assertEquals(1, $progress['completed']);
        $this->assertEquals(50, $progress['percentage']);
    }
}
