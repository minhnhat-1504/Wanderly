<?php

namespace Tests\Unit;

use App\Services\Itinerary\PlaceRepository;
use Tests\TestCase;

class PlaceRepositoryTest extends TestCase
{
    protected PlaceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PlaceRepository();
    }

    public function test_loads_verified_places_for_seeded_provinces(): void
    {
        $provinces = ['danang', 'lamdong', 'khanhhoa', 'hanoi', 'quangninh'];

        foreach ($provinces as $code) {
            $places = $this->repository->getVerifiedByProvince($code);
            $this->assertGreaterThanOrEqual(30, count($places), "Province {$code} must have at least 30 verified places.");

            foreach ($places as $place) {
                $this->assertTrue($place['verified'], "Place {$place['id']} must be verified.");
                $this->assertNotSame('llm_draft', $place['source'], "Verified places must not be llm_draft.");
                $this->assertSame($code, $place['province_code']);
                $this->assertNotEmpty($place['name_vi']);
                $this->assertIsFloat($place['lat']);
                $this->assertIsFloat($place['lng']);
            }
        }
    }

    public function test_can_find_place_by_id(): void
    {
        $place = $this->repository->getById('dn_001');
        $this->assertNotNull($place);
        $this->assertSame('Sun World Bà Nà Hills & Cầu Vàng', $place['name_vi']);
        $this->assertSame('danang', $place['province_code']);

        $placeHanoi = $this->repository->getById('hn_001');
        $this->assertNotNull($placeHanoi);
        $this->assertSame('Hồ Hoàn Kiếm & Đền Ngọc Sơn', $placeHanoi['name_vi']);
    }

    public function test_returns_null_for_non_existent_id(): void
    {
        $place = $this->repository->getById('non_existent_999');
        $this->assertNull($place);
    }

    public function test_can_filter_places_by_category_and_season(): void
    {
        // Filter beaches in Da Nang
        $beaches = $this->repository->filterPlaces('danang', [
            'categories' => ['beach'],
        ]);
        $this->assertNotEmpty($beaches);
        foreach ($beaches as $b) {
            $this->assertSame('beach', $b['category']);
        }

        // Filter indoor places in Hanoi
        $indoorHanoi = $this->repository->filterPlaces('hanoi', [
            'indoor' => true,
        ]);
        $this->assertNotEmpty($indoorHanoi);
        foreach ($indoorHanoi as $p) {
            $this->assertTrue($p['indoor']);
        }
    }

    public function test_provides_required_legal_attributions(): void
    {
        $attributions = $this->repository->getAttributions();
        $this->assertArrayHasKey('osm', $attributions);
        $this->assertArrayHasKey('wikivoyage', $attributions);
        $this->assertArrayHasKey('weather', $attributions);
        $this->assertArrayHasKey('disclaimer', $attributions);
        $this->assertStringContainsString('OpenStreetMap', $attributions['osm']);
        $this->assertStringContainsString('Wikivoyage', $attributions['wikivoyage']);
        $this->assertStringContainsString('Open-Meteo', $attributions['weather']);
    }
}
