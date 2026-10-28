<?php

namespace Tests\Unit;

use App\Services\Itinerary\PlaceRepository;
use App\Services\Itinerary\ProvinceResolver;
use App\Services\Itinerary\TravelTimeService;
use Tests\TestCase;

class TravelTimeServiceTest extends TestCase
{
    protected TravelTimeService $travelService;

    protected PlaceRepository $placeRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->placeRepository = new PlaceRepository;
        $provinceResolver = new ProvinceResolver;
        $this->travelService = new TravelTimeService($this->placeRepository, $provinceResolver);
    }

    public function test_haversine_formula_calculates_accurate_distance(): void
    {
        // Da Nang Dragon Bridge (16.0611, 108.2272) to My Khe Beach (16.0594, 108.2467)
        // Straight line distance is approximately 2.1 km
        $dist = $this->travelService->haversine(16.0611, 108.2272, 16.0594, 108.2467);
        $this->assertGreaterThan(1.8, $dist);
        $this->assertLessThan(2.5, $dist);
    }

    public function test_same_place_returns_zero_minutes(): void
    {
        $minutes = $this->travelService->travel_minutes('dn_001', 'dn_001', 'danang');
        $this->assertSame(0, $minutes);

        $place = $this->placeRepository->getById('hn_001');
        $minutesSelf = $this->travelService->travel_minutes($place, $place, 'hanoi');
        $this->assertSame(0, $minutesSelf);
    }

    public function test_travel_time_between_realistic_destinations(): void
    {
        // Cầu Rồng (dn_002) to Sun World Bà Nà Hills (dn_001) in Da Nang
        // Approx 26km direct, ~36km road distance, travel time ~45-60 min
        $minutes = $this->travelService->travel_minutes('dn_002', 'dn_001', 'danang');
        $this->assertGreaterThanOrEqual(40, $minutes);
        $this->assertLessThanOrEqual(75, $minutes);

        // Hồ Hoàn Kiếm (hn_001) to Văn Miếu (hn_002) in Hanoi
        // Central city short trip, ~2km, approx 10-20 min
        $minutesHn = $this->travelService->travel_minutes('hn_001', 'hn_002', 'hanoi');
        $this->assertGreaterThanOrEqual(10, $minutesHn);
        $this->assertLessThanOrEqual(25, $minutesHn);
    }

    public function test_terrain_factor_increases_mountain_travel_time(): void
    {
        // Two synthetic points 20km apart
        $pointFlatA = ['lat' => 21.0000, 'lng' => 105.8000, 'name_vi' => 'Point A'];
        $pointFlatB = ['lat' => 21.1800, 'lng' => 105.8000, 'name_vi' => 'Point B'];

        // Compute under flatland / coast (e.g. central_coast terrain factor 1.20)
        $flatMinutes = $this->travelService->computeTravelMinutes($pointFlatA, $pointFlatB, 'danang');

        // Compute under mountain (e.g. north_mountain terrain factor 1.45, speed 32 kmh)
        $mountainMinutes = $this->travelService->computeTravelMinutes($pointFlatA, $pointFlatB, 'laocai');

        $this->assertGreaterThan($flatMinutes, $mountainMinutes, 'Mountain travel time must be higher than flatland for same distance.');
    }

    public function test_island_crossing_adds_ferry_penalty(): void
    {
        // Cầu Rồng (mainland) to Cù Lao Chàm (island dn_024)
        $minutesIsland = $this->travelService->travel_minutes('dn_002', 'dn_024', 'danang');

        // Cầu Rồng to a mainland spot at similar distance
        $dist = $this->travelService->distance_km('dn_002', 'dn_024');
        $this->assertGreaterThan(25.0, $dist);

        // Island crossing must take at least 50 minutes (road + boat + boarding overhead)
        $this->assertGreaterThanOrEqual(50, $minutesIsland);
    }

    public function test_can_load_precomputed_matrix_from_disk(): void
    {
        $matrix = $this->travelService->getProvinceMatrix('danang');
        $this->assertNotEmpty($matrix);
        $this->assertArrayHasKey('dn_001', $matrix);
        $this->assertArrayHasKey('dn_002', $matrix['dn_001']);
        $this->assertIsInt($matrix['dn_001']['dn_002']);
    }
}
