<?php

namespace Tests\Unit;

use App\Services\Itinerary\OpenMeteoWeatherService;
use App\Services\Itinerary\ProvinceResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenMeteoWeatherServiceTest extends TestCase
{
    protected OpenMeteoWeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        $resolver = new ProvinceResolver();
        $this->weatherService = new OpenMeteoWeatherService($resolver);
    }

    public function test_displays_required_open_meteo_attribution(): void
    {
        $summary = $this->weatherService->getWeatherSummary('danang', now()->format('Y-m-d'), 3);

        $this->assertSame('Weather data by Open-Meteo.com', $summary['attribution']);
        $this->assertSame('https://open-meteo.com/', $summary['attribution_url']);
    }

    public function test_uses_forecast_when_trip_is_within_16_days(): void
    {
        $startDate = now()->addDays(3)->format('Y-m-d');

        // Mock Open-Meteo API response
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'daily' => [
                    'time' => [$startDate, now()->addDays(4)->format('Y-m-d'), now()->addDays(5)->format('Y-m-d')],
                    'temperature_2m_max' => [32.5, 31.0, 30.0],
                    'temperature_2m_min' => [25.0, 24.5, 24.0],
                    'precipitation_sum' => [0.0, 1.2, 0.0],
                    'weathercode' => [0, 2, 1],
                ]
            ], 200)
        ]);

        $summary = $this->weatherService->getWeatherSummary('danang', $startDate, 3);

        $this->assertSame('forecast', $summary['mode']);
        $this->assertCount(3, $summary['daily']);
        $this->assertSame(32.5, $summary['daily'][0]['temp_max']);
        $this->assertTrue($summary['daily'][0]['is_outdoor_suitable']);
    }

    public function test_falls_back_to_climate_rules_when_trip_is_beyond_16_days(): void
    {
        $futureDate = now()->addDays(45)->format('Y-m-d');

        $summary = $this->weatherService->getWeatherSummary('hanoi', $futureDate, 3);

        $this->assertSame('climate_rules', $summary['mode']);
        $this->assertCount(3, $summary['daily']);
        $this->assertNotEmpty($summary['general_advice']);
    }

    public function test_detects_central_coast_storm_season_in_october(): void
    {
        // October in Da Nang (month 10) is storm season
        $octoberDate = '2026-10-15';
        $summary = $this->weatherService->getWeatherSummary('danang', $octoberDate, 3);

        $this->assertNotEmpty($summary['season_hazards']);
        $hazardKeys = array_column($summary['season_hazards'], 'key');
        $this->assertContains('central_storm_season', $hazardKeys);
        $this->assertFalse($summary['daily'][0]['is_outdoor_suitable']);
    }

    public function test_degrades_gracefully_when_open_meteo_network_fails(): void
    {
        $startDate = now()->addDays(2)->format('Y-m-d');

        // Force network failure (HTTP 503 Service Unavailable)
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response(['error' => 'Overloaded'], 503)
        ]);

        $summary = $this->weatherService->getWeatherSummary('lamdong', $startDate, 2);

        // Must degrade gracefully to climate_rules without crashing
        $this->assertSame('climate_rules', $summary['mode']);
        $this->assertCount(2, $summary['daily']);
        $this->assertNotNull($summary['attribution']);
    }

    public function test_interprets_wmo_weather_codes_in_vietnamese(): void
    {
        $clear = $this->weatherService->interpretWmoCode(0);
        $this->assertStringContainsString('nắng đẹp', $clear);

        $storm = $this->weatherService->interpretWmoCode(95);
        $this->assertStringContainsString('Dông', $storm);

        $heavyRain = $this->weatherService->interpretWmoCode(65);
        $this->assertStringContainsString('Mưa to', $heavyRain);
    }
}
