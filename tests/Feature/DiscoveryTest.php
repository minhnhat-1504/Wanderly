<?php

namespace Tests\Feature;

use App\Models\DiscoveryArticle;
use App\Models\DiscoveryRegion;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_discovery_index(): void
    {
        $region = DiscoveryRegion::create([
            'name' => 'Northern Vietnam',
            'slug' => 'northern-vietnam',
            'description' => 'Mountains and valleys',
        ]);

        DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Ha Giang Loop Guide',
            'slug' => 'ha-giang-loop-guide',
            'category' => 'nature',
            'summary' => 'Spectacular karst mountains and winding passes.',
            'content' => 'Full guide content goes here...',
            'is_published' => true,
        ]);

        $response = $this->get(route('discovery.index'));

        $response->assertStatus(200);
        $response->assertSee('Vietnam Travel Discovery Portal');
        $response->assertSee('Ha Giang Loop Guide');
        $response->assertSee('Northern Vietnam');
    }

    public function test_guest_can_search_and_filter_discovery_articles(): void
    {
        $north = DiscoveryRegion::create([
            'name' => 'Northern Vietnam',
            'slug' => 'northern-vietnam',
        ]);

        $central = DiscoveryRegion::create([
            'name' => 'Central Vietnam',
            'slug' => 'central-vietnam',
        ]);

        $artNorth = DiscoveryArticle::create([
            'region_id' => $north->id,
            'title' => 'Ha Giang Hidden Passes',
            'slug' => 'ha-giang-hidden-passes',
            'category' => 'nature',
            'content' => 'Exploring Dong Van karst plateau and rocky mountain crevices',
            'is_published' => true,
        ]);

        $artCentral = DiscoveryArticle::create([
            'region_id' => $central->id,
            'title' => 'Hoi An Silk Lantern Craft',
            'slug' => 'hoi-an-silk-lantern-craft',
            'category' => 'culture',
            'content' => 'Centuries of silk weaving along the riverbanks',
            'is_published' => true,
        ]);

        // Filter by region = central-vietnam
        $responseRegion = $this->get(route('discovery.index', ['region' => 'central-vietnam']));
        $responseRegion->assertStatus(200);
        $responseRegion->assertSee('Hoi An Silk Lantern Craft');
        $responseRegion->assertDontSee('Ha Giang Hidden Passes');

        // Filter by category = nature
        $responseCat = $this->get(route('discovery.index', ['category' => 'nature']));
        $responseCat->assertStatus(200);
        $responseCat->assertSee('Ha Giang Hidden Passes');
        $responseCat->assertDontSee('Hoi An Silk Lantern Craft');

        // Search by keyword = Dong Van
        $responseSearch = $this->get(route('discovery.index', ['search' => 'Dong Van']));
        $responseSearch->assertStatus(200);
        $responseSearch->assertSee('Ha Giang Hidden Passes');
        $responseSearch->assertDontSee('Hoi An Silk Lantern Craft');
    }

    public function test_guest_can_view_single_discovery_article_and_increments_views(): void
    {
        $region = DiscoveryRegion::create([
            'name' => 'Southern Vietnam',
            'slug' => 'southern-vietnam',
        ]);

        $article = DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Phu Quoc Sunset Guide',
            'slug' => 'phu-quoc-sunset-guide',
            'category' => 'nature',
            'summary' => 'Tropical beaches and coral gardens',
            'content' => 'Crystal waters, silky white sand, and fiery sunsets.',
            'views_count' => 10,
            'is_published' => true,
        ]);

        $response = $this->get(route('discovery.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('Phu Quoc Sunset Guide');
        $response->assertSee('About this Destination');

        $this->assertEquals(11, $article->fresh()->views_count);
    }

    public function test_guest_cannot_add_discovery_article_to_trip(): void
    {
        $region = DiscoveryRegion::create([
            'name' => 'Northern Vietnam',
            'slug' => 'northern-vietnam',
        ]);

        $article = DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Nho Que Boat Ride',
            'slug' => 'nho-que-boat-ride',
            'category' => 'nature',
            'content' => 'Boating through Tu San Canyon',
            'is_published' => true,
        ]);

        $trip = Trip::factory()->create();

        $response = $this->post(route('discovery.add-to-trip', $article), [
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_trip_leader_can_add_discovery_article_to_trip(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create([
            'owner_id' => $user->id,
            'name' => 'Northern Loop',
        ]);

        $region = DiscoveryRegion::create([
            'name' => 'Northern Vietnam',
            'slug' => 'northern-vietnam',
        ]);

        $article = DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Ma Pi Leng Panoramic View',
            'slug' => 'ma-pi-leng-panoramic-view',
            'category' => 'nature',
            'location_name' => 'Dong Van, Ha Giang',
            'summary' => 'Breathtaking mountain pass overlook',
            'content' => 'Overlooking the turquoise river below...',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->post(route('discovery.add-to-trip', $article), [
            'trip_id' => $trip->id,
            'day' => 2,
            'time_slot' => 'afternoon',
        ]);

        $response->assertRedirect(route('trips.show', $trip));

        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'title' => 'Explore: Ma Pi Leng Panoramic View',
            'day' => 2,
            'time_slot' => 'afternoon',
            'category' => 'sightseeing',
            'location' => 'Dong Van, Ha Giang',
        ]);
    }

    public function test_ajax_add_to_trip_returns_json(): void
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $user->id]);

        $region = DiscoveryRegion::create(['name' => 'Central Vietnam', 'slug' => 'central-vietnam']);
        $article = DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Ba Buoi Chicken Rice',
            'slug' => 'ba-buoi-chicken-rice',
            'category' => 'cuisine',
            'location_name' => 'Hoi An, Quang Nam',
            'content' => 'Authentic central style shredded chicken rice.',
            'is_published' => true,
        ]);

        $response = $this->actingAs($user)->postJson(route('discovery.add-to-trip', $article), [
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'midday',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('activities', [
            'trip_id' => $trip->id,
            'title' => 'Explore: Ba Buoi Chicken Rice',
            'category' => 'dining',
            'time_slot' => 'midday',
        ]);
    }

    public function test_stranger_cannot_add_article_to_foreign_trip(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $trip = Trip::factory()->create(['owner_id' => $owner->id]);

        $region = DiscoveryRegion::create(['name' => 'Northern Vietnam', 'slug' => 'northern-vietnam']);
        $article = DiscoveryArticle::create([
            'region_id' => $region->id,
            'title' => 'Sample Article',
            'slug' => 'sample-article',
            'category' => 'nature',
            'content' => 'Test content',
            'is_published' => true,
        ]);

        $response = $this->actingAs($stranger)->post(route('discovery.add-to-trip', $article), [
            'trip_id' => $trip->id,
            'day' => 1,
            'time_slot' => 'morning',
        ]);

        $response->assertStatus(403);
    }
}
