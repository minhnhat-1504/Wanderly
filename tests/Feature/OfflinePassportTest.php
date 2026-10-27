<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\ChecklistItem;
use App\Models\Destination;
use App\Models\Provider;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use App\Services\OfflinePassportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflinePassportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_offline_passport(): void
    {
        $trip = Trip::factory()->create(['privacy' => 'private']);

        $response = $this->get(route('trips.offline-passport', $trip));

        $response->assertRedirect(route('login'));
    }

    public function test_trip_owner_can_view_offline_passport(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Ha Giang Adventure',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(3)->format('Y-m-d'),
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user)->get(route('trips.offline-passport', $trip));

        $response->assertStatus(200);
        $response->assertSee('Ha Giang Adventure');
        $response->assertSee('Offline Travel Passport');
        $response->assertSee('Emergency Hotlines');
    }

    public function test_viewer_member_can_view_offline_passport(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => $viewer->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($viewer)->get(route('trips.offline-passport', $trip));

        $response->assertStatus(200);
    }

    public function test_stranger_cannot_view_private_offline_passport(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id, 'privacy' => 'private']);

        $response = $this->actingAs($stranger)->get(route('trips.offline-passport', $trip));

        $response->assertStatus(403);
    }

    public function test_offline_passport_data_endpoint_returns_json(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Phu Quoc Retreat',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(2)->format('Y-m-d'),
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($owner)->getJson(route('trips.offline-passport.data', $trip));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'trip' => ['id', 'name', 'destination', 'start_date', 'end_date', 'duration_days'],
                'timeline',
                'vouchers',
                'emergency' => ['national', 'leader', 'providers'],
                'checklist',
                'generated_at',
            ],
        ]);
    }

    public function test_offline_passport_service_bundles_activities_and_vouchers(): void
    {
        $owner = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Da Nang Coastline',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]);

        Activity::create([
            'trip_id' => $trip->id,
            'title' => 'Dragon Bridge Fire Show',
            'category' => 'sightseeing',
            'location' => 'Dragon Bridge, Da Nang',
            'day' => 1,
            'time_slot' => 'evening',
            'position' => 0,
            'cost' => 0,
            'address' => 'Han River, Da Nang',
        ]);

        $destination = Destination::firstOrCreate(
            ['name' => 'Da Nang'],
            ['description' => 'Coastal city']
        );

        $provider = Provider::create([
            'user_id' => $owner->id,
            'destination_id' => $destination->id,
            'business_name' => 'Da Nang Motor Rental',
            'phone' => '0901234567',
            'service_type' => 'transport',
        ]);

        Booking::create([
            'trip_id' => $trip->id,
            'provider_id' => $provider->id,
            'item_name' => 'Honda XR 150cc',
            'price' => 350000,
            'voucher_code' => 'WND-MTR-001',
            'status' => 'confirmed',
        ]);

        ChecklistItem::create([
            'trip_id' => $trip->id,
            'title' => 'National ID / Passport',
            'is_completed' => true,
            'category' => 'Documents',
            'position' => 0,
        ]);

        $service = app(OfflinePassportService::class);
        $snapshot = $service->generateOfflineSnapshot($trip);

        $this->assertEquals('Da Nang Coastline', $snapshot['trip']['name']);
        $this->assertEquals(3, $snapshot['trip']['duration_days']);
        $this->assertCount(3, $snapshot['timeline']);
        $this->assertEquals('Dragon Bridge Fire Show', $snapshot['timeline'][0]['activities'][0]['title']);

        $this->assertCount(1, $snapshot['vouchers']);
        $this->assertEquals('WND-MTR-001', $snapshot['vouchers'][0]['voucher_code']);
        $this->assertStringContainsString('WND:VOUCHER:WND-MTR-001', $snapshot['vouchers'][0]['qr_payload']);

        $this->assertNotEmpty($snapshot['emergency']['national']);
        $this->assertCount(1, $snapshot['emergency']['providers']);
        $this->assertEquals('0901234567', $snapshot['emergency']['providers'][0]['phone']);

        $this->assertCount(1, $snapshot['checklist']);
        $this->assertEquals('National ID / Passport', $snapshot['checklist'][0]['title']);
    }
}
