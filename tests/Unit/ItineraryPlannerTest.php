<?php

namespace Tests\Unit;

use App\Services\Itinerary\ItineraryPlannerService;
use App\Services\Itinerary\ItineraryValidator;
use App\Services\Itinerary\OpenMeteoWeatherService;
use App\Services\Itinerary\PlaceRepository;
use App\Services\Itinerary\ProvinceResolver;
use App\Services\Itinerary\TravelTimeService;
use Tests\TestCase;

class ItineraryPlannerTest extends TestCase
{
    protected ItineraryPlannerService $planner;
    protected ItineraryValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $resolver = new ProvinceResolver();
        $placeRepo = new PlaceRepository();
        $travelTime = new TravelTimeService($placeRepo, $resolver);
        $weatherService = new OpenMeteoWeatherService($resolver);
        $this->validator = new ItineraryValidator($placeRepo, $travelTime);

        $this->planner = new ItineraryPlannerService(
            $resolver,
            $placeRepo,
            $travelTime,
            $weatherService,
            $this->validator
        );
    }

    public function test_asks_for_clarification_on_unknown_province(): void
    {
        $result = $this->planner->plan('Atlantis Không Tồn Tại', 3);

        $this->assertEquals('clarification_needed', $result['status']);
        $this->assertNotEmpty($result['message']);
    }

    public function test_greedy_fallback_generates_valid_plan_for_danang(): void
    {
        $result = $this->planner->plan('danang', 3, [
            'force_greedy' => true,
            'month' => 4,
        ]);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('deterministic_greedy', $result['source_type']);
        $this->assertCount(3, $result['days']);

        // Check required fields
        $this->assertNotEmpty($result['province']['name']);
        $this->assertNotEmpty($result['attributions']['map_data']);
        $this->assertNotEmpty($result['attributions']['weather_data']);
        $this->assertNotEmpty($result['disclaimer']);

        // Check each day items have verified places and valid Google Maps links
        foreach ($result['days'] as $day) {
            $this->assertGreaterThanOrEqual(3, count($day['items']));
            foreach ($day['items'] as $item) {
                $this->assertTrue($item['place']['verified']);
                $this->assertStringStartsWith('https://www.google.com/maps/search/', $item['place']['google_maps_url']);
                $this->assertNotEmpty($item['place']['ticket_price_vnd']);
            }
        }
    }

    public function test_greedy_fallback_generates_valid_plan_for_lamdong(): void
    {
        $result = $this->planner->plan('Lâm Đồng (Đà Lạt)', 2, [
            'force_greedy' => true,
            'month' => 12,
        ]);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $result['days']);
        $this->assertEquals('lamdong', $result['province']['code']);
    }

    public function test_never_outputs_administrative_units_huyen_quan_thi_xa(): void
    {
        $result = $this->planner->plan('Quảng Ninh', 2, [
            'force_greedy' => true,
            'month' => 5,
        ]);

        $jsonEncoded = json_encode($result, JSON_UNESCAPED_UNICODE);

        $this->assertDoesNotMatchRegularExpression('/\b(quận|huyện|thị xã)\s+[A-ZÀ-Ỵ]/iu', $jsonEncoded);
    }
}
