<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Message;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\ReverseMemoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryScrapbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_memory_scrapbook(): void
    {
        $trip = Trip::factory()->create(['privacy' => 'private']);

        $response = $this->get(route('trips.memory-scrapbook', $trip));

        $response->assertRedirect(route('login'));
    }

    public function test_trip_owner_can_view_memory_scrapbook(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Ha Giang Memory Tour',
            'destination' => 'Ha Giang',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->get(route('trips.memory-scrapbook', $trip));

        $response->assertStatus(200);
        $response->assertSee('Ha Giang Memory Tour');
        $response->assertSee('Reverse Memory Scrapbook');
        $response->assertSee('Share to Community Feed');
    }

    public function test_viewer_can_view_memory_scrapbook(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewer)->get(route('trips.memory-scrapbook', $trip));

        $response->assertStatus(200);
    }

    public function test_stranger_cannot_view_private_memory_scrapbook(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);

        $response = $this->actingAs($stranger)->get(route('trips.memory-scrapbook', $trip));

        $response->assertStatus(403);
    }

    public function test_memory_scrapbook_data_endpoint_returns_json(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Hoi An Lantern Magic',
            'destination' => 'Hoi An',
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($owner)->getJson(route('trips.memory-scrapbook.data', $trip));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'trip',
                'stats' => ['total_photos', 'total_milestones', 'duration_days', 'participants_count'],
                'days',
                'participants',
            ],
        ]);
    }

    public function test_reverse_memory_service_extracts_chat_attachments(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Da Lat Coffee Escape',
            'destination' => 'Da Lat',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $member->id,
            'role' => 'editor',
        ]);

        // Add milestone activity
        Activity::create([
            'trip_id' => $trip->id,
            'title' => 'Cloud Hunting at Cau Dat',
            'category' => 'sightseeing',
            'location' => 'Cau Dat Tea Hill, Da Lat',
            'day' => 1,
            'time_slot' => 'morning',
            'position' => 0,
            'cost' => 0,
        ]);

        // Add message with photo attachment
        Message::create([
            'trip_id' => $trip->id,
            'sender_id' => $member->id,
            'content' => 'Sunrise above the mist was unreal!',
            'attachment' => 'https://example.com/photos/mist_sunrise.jpg',
            'created_at' => '2026-11-01 06:30:00',
        ]);

        $service = app(ReverseMemoryService::class);
        $scrapbook = $service->generateScrapbook($trip);

        $this->assertEquals('Da Lat Coffee Escape', $scrapbook['trip']['name']);
        $this->assertEquals(3, $scrapbook['stats']['duration_days']);
        $this->assertEquals(2, $scrapbook['stats']['participants_count']); // owner + 1 member
        $this->assertEquals(1, $scrapbook['stats']['total_photos']);
        $this->assertEquals(1, $scrapbook['stats']['total_milestones']);

        // Check Day 1 memories
        $day1 = $scrapbook['days'][0];
        $this->assertEquals(1, $day1['day']);
        $this->assertCount(2, $day1['memories']); // 1 photo + 1 milestone

        // Verify photo memory
        $photoMemory = collect($day1['memories'])->firstWhere('type', 'chat_photo');
        $this->assertNotNull($photoMemory);
        $this->assertEquals('https://example.com/photos/mist_sunrise.jpg', $photoMemory['image_url']);
        $this->assertEquals('Sunrise above the mist was unreal!', $photoMemory['caption']);
        $this->assertEquals($member->name, $photoMemory['author_name']);

        // Verify milestone memory
        $milestoneMemory = collect($day1['memories'])->firstWhere('type', 'activity_milestone');
        $this->assertNotNull($milestoneMemory);
        $this->assertEquals('Cloud Hunting at Cau Dat', $milestoneMemory['title']);
    }
}
