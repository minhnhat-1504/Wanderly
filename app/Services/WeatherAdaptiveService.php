<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherAdaptiveService
{
    /**
     * Coordinates for typical Vietnam travel hubs.
     *
     * @var array<string, array{lat: float, lon: float}>
     */
    protected array $destinationCoordinates = [
        'hà giang' => ['lat' => 22.8233, 'lon' => 104.9839],
        'ha giang' => ['lat' => 22.8233, 'lon' => 104.9839],
        'hội an' => ['lat' => 15.8801, 'lon' => 108.3380],
        'hoi an' => ['lat' => 15.8801, 'lon' => 108.3380],
        'đà nẵng' => ['lat' => 16.0544, 'lon' => 108.2022],
        'da nang' => ['lat' => 16.0544, 'lon' => 108.2022],
        'phú quốc' => ['lat' => 10.2899, 'lon' => 103.9840],
        'phu quoc' => ['lat' => 10.2899, 'lon' => 103.9840],
        'sapa' => ['lat' => 22.3364, 'lon' => 103.8438],
        'hà nội' => ['lat' => 21.0285, 'lon' => 105.8542],
        'ha noi' => ['lat' => 21.0285, 'lon' => 105.8542],
        'hồ chí minh' => ['lat' => 10.8231, 'lon' => 106.6297],
        'ho chi minh' => ['lat' => 10.8231, 'lon' => 106.6297],
    ];

    /**
     * Get short-term weather forecast for a trip destination.
     *
     * @return array{destination: string, current_temp: float, condition: string, has_rain: bool, forecast_days: array}
     */
    public function getForecast(Trip $trip): array
    {
        $destName = mb_strtolower($trip->destination ?? 'đà nẵng');
        $coords = $this->destinationCoordinates['đà nẵng'];

        foreach ($this->destinationCoordinates as $name => $geo) {
            if (str_contains($destName, $name)) {
                $coords = $geo;
                break;
            }
        }

        try {
            // Use free Open-Meteo API without authentication
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $coords['lat'],
                'longitude' => $coords['lon'],
                'current' => 'temperature_2m,precipitation,weather_code',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                'timezone' => 'Asia/Bangkok',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $precip = (float) ($data['current']['precipitation'] ?? 0);
                $weatherCode = (int) ($data['current']['weather_code'] ?? 0);
                $hasRain = $precip > 0.5 || in_array($weatherCode, [51, 53, 55, 61, 63, 65, 80, 81, 82, 95, 96, 99], true);

                return [
                    'destination' => $trip->destination?->name ?? 'Vietnam',
                    'current_temp' => (float) ($data['current']['temperature_2m'] ?? 28),
                    'condition' => $hasRain ? 'Mưa rào / Có mưa' : 'Nắng ráo / Thoáng đãng',
                    'has_rain' => $hasRain,
                    'forecast_days' => $data['daily'] ?? [],
                ];
            }
        } catch (\Throwable $e) {
            Log::info('Open-Meteo call failed, using mock weather data: '.$e->getMessage());
        }

        // Reliable fallback weather
        return [
            'destination' => $trip->destination?->name ?? 'Vietnam',
            'current_temp' => 28.5,
            'condition' => 'Nắng đẹp',
            'has_rain' => false,
            'forecast_days' => [],
        ];
    }

    /**
     * Inspect timeline activities and detect outdoor activities that conflict with rain.
     *
     * @return array<int, array{activity_id: int, title: string, day: int, time_slot: string, warning: string, suggestion: array}>
     */
    public function detectWeatherConflicts(Trip $trip): array
    {
        $forecast = $this->getForecast($trip);
        $conflicts = [];

        $outdoorKeywords = ['trekking', 'leo núi', 'tắm biển', 'chèo thuyền', 'kayak', 'ngoài trời', 'xe máy', 'đi bộ', 'hiking', 'camping'];

        $activities = $trip->activities()->get();

        foreach ($activities as $act) {
            $titleLower = mb_strtolower($act->title);
            $isOutdoor = false;
            foreach ($outdoorKeywords as $kw) {
                if (str_contains($titleLower, $kw)) {
                    $isOutdoor = true;
                    break;
                }
            }

            // Flag if forecast indicates rain and activity is outdoor
            if ($isOutdoor && $forecast['has_rain']) {
                $conflicts[] = [
                    'activity_id' => $act->id,
                    'title' => $act->title,
                    'day' => (int) $act->day,
                    'time_slot' => $act->time_slot,
                    'warning' => "Dự báo có mưa rào tại {$forecast['destination']}, có thể gây nguy hiểm hoặc cản trở hoạt động ngoài trời này.",
                    'suggestion' => [
                        'type' => 'replace_indoor',
                        'title' => "Bảo tàng / Quán Cafe Văn hóa ({$forecast['destination']})",
                        'reason' => 'Không gian trong nhà an toàn, ấm cúng và mang đậm bản sắc bản địa.',
                    ],
                ];
            }
        }

        return $conflicts;
    }
}
