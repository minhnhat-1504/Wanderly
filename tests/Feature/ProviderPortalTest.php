<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Destination;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $providerUser;

    protected Provider $provider;

    protected Destination $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destination = Destination::factory()->create(['name' => 'Da Lat']);

        $this->providerUser = User::factory()->create(['role' => 'provider']);
        $this->provider = Provider::factory()->create([
            'user_id' => $this->providerUser->id,
            'destination_id' => $this->destination->id,
            'business_name' => 'Highland Transfers & Tours',
            'service_type' => 'transport',
        ]);
    }

    public function test_guest_cannot_access_provider_portal(): void
    {
        $response = $this->get(route('provider.dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_traveler_can_view_registration_form(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);

        $response = $this->actingAs($traveler)->get(route('provider.register'));
        $response->assertStatus(200);
        $response->assertSee('Become a Verified Service Provider');
    }

    public function test_traveler_can_register_as_provider(): void
    {
        $traveler = User::factory()->create(['role' => 'traveler']);

        $response = $this->actingAs($traveler)->post(route('provider.register.store'), [
            'business_name' => 'Da Lat Eco Trekking',
            'service_type' => 'tour',
            'destination_id' => $this->destination->id,
            'phone' => '+84 901 234 567',
            'address' => '45 Tran Phu, Da Lat',
            'bio' => 'Professional mountain guides and camping tours.',
        ]);

        $response->assertRedirect(route('provider.contract'));
        $this->assertDatabaseHas('providers', [
            'user_id' => $traveler->id,
            'business_name' => 'Da Lat Eco Trekking',
            'service_type' => 'tour',
        ]);

        $this->assertEquals('provider', $traveler->fresh()->role);
    }

    public function test_provider_can_view_and_sign_contract(): void
    {
        $response = $this->actingAs($this->providerUser)->get(route('provider.contract'));
        $response->assertStatus(200);
        $response->assertSee('Wanderly Partner Cooperation Agreement');
        $response->assertSee($this->provider->business_name);

        $signResponse = $this->actingAs($this->providerUser)->post(route('provider.contract.sign'), [
            'agree_terms' => '1',
            'signature_name' => 'Test Representative',
        ]);

        $signResponse->assertRedirect(route('provider.dashboard'));
        $this->assertTrue($this->provider->fresh()->is_verified);
    }

    public function test_registered_provider_is_redirected_away_from_registration_form(): void
    {
        $response = $this->actingAs($this->providerUser)->get(route('provider.register'));
        $response->assertRedirect(route('provider.dashboard'));
    }

    public function test_provider_can_view_dashboard_with_kpis(): void
    {
        // Seed one booking for the provider
        $trip = Trip::factory()->create(['destination' => 'Da Lat']);
        Booking::factory()->create([
            'trip_id' => $trip->id,
            'provider_id' => $this->provider->id,
            'price' => 1500000.00,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->providerUser)->get(route('provider.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Highland Transfers & Tours');
        $response->assertSee('Revenue Performance');
        $response->assertSee('1.500.000');
    }

    public function test_provider_can_create_catalog_service_with_specs_and_image(): void
    {
        Storage::fake('public');

        $image = UploadedFile::fake()->image('car.jpg', 600, 400);

        $response = $this->actingAs($this->providerUser)->post(route('provider.services.store'), [
            'name' => 'VIP 7-Seater Fortuner Rental',
            'price' => 2000000.00,
            'tags' => 'transport, suv, private, 7seats',
            'specs' => [
                ['key' => 'seats', 'value' => '7'],
                ['key' => 'transmission', 'value' => 'automatic'],
                ['key' => 'fuel', 'value' => 'diesel'],
            ],
            'image' => $image,
        ]);

        $response->assertRedirect(route('provider.services.index'));

        $this->assertDatabaseHas('services', [
            'provider_id' => $this->provider->id,
            'name' => 'VIP 7-Seater Fortuner Rental',
            'price' => 2000000.00,
        ]);

        $service = Service::where('name', 'VIP 7-Seater Fortuner Rental')->first();
        $this->assertNotNull($service->image);
        Storage::disk('public')->assertExists($service->image);
        $this->assertEquals(7, $service->specs['seats']);
        $this->assertContains('transport', $service->tags);
    }

    public function test_provider_can_update_service_and_remove_image(): void
    {
        Storage::fake('public');
        $oldImage = UploadedFile::fake()->image('old.jpg')->store('services', 'public');

        $service = Service::factory()->create([
            'provider_id' => $this->provider->id,
            'name' => 'Original Tour',
            'price' => 500000.00,
            'image' => $oldImage,
        ]);

        $response = $this->actingAs($this->providerUser)->put(route('provider.services.update', $service), [
            'name' => 'Updated Tour Package',
            'price' => 750000.00,
            'tags' => 'tour, updated',
            'specs' => [
                ['key' => 'duration', 'value' => 'full-day'],
            ],
            'remove_image' => '1',
        ]);

        $response->assertRedirect(route('provider.services.index'));

        $fresh = $service->fresh();
        $this->assertEquals('Updated Tour Package', $fresh->name);
        $this->assertEquals(750000.00, $fresh->price);
        $this->assertNull($fresh->image);
        Storage::disk('public')->assertMissing($oldImage);
    }

    public function test_other_provider_cannot_update_foreign_service(): void
    {
        $otherUser = User::factory()->create(['role' => 'provider']);
        $otherProvider = Provider::factory()->create(['user_id' => $otherUser->id]);

        $service = Service::factory()->create(['provider_id' => $otherProvider->id]);

        $response = $this->actingAs($this->providerUser)->put(route('provider.services.update', $service), [
            'name' => 'Hacked Service',
            'price' => 1000.00,
        ]);

        $response->assertStatus(403);
    }

    public function test_provider_can_block_and_unblock_calendar_date(): void
    {
        $futureDate = Carbon::today()->addDays(5)->toDateString();

        // Block date
        $response = $this->actingAs($this->providerUser)->postJson(route('ajax.provider.calendar.block'), [
            'date' => $futureDate,
            'reason' => 'Private company contract',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('provider_schedules', [
            'provider_id' => $this->provider->id,
            'reason' => 'Private company contract',
        ]);
        $this->assertTrue(
            $this->provider->schedules()->whereDate('blocked_date', $futureDate)->exists()
        );

        // Unblock date
        $unblockRes = $this->actingAs($this->providerUser)->postJson(route('ajax.provider.calendar.unblock'), [
            'date' => $futureDate,
        ]);

        $unblockRes->assertStatus(200);
        $this->assertFalse(
            $this->provider->schedules()->whereDate('blocked_date', $futureDate)->exists()
        );
    }

    public function test_provider_cannot_block_past_date(): void
    {
        $pastDate = Carbon::yesterday()->toDateString();

        $response = $this->actingAs($this->providerUser)->postJson(route('ajax.provider.calendar.block'), [
            'date' => $pastDate,
        ]);

        $response->assertStatus(422);
    }
}
