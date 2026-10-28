<?php

namespace App\Services\Itinerary;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenMeteoWeatherService
{
    public const ATTRIBUTION_TEXT = 'Weather data by Open-Meteo.com';
    public const ATTRIBUTION_URL = 'https://open-meteo.com/';

    protected array $regionRules = [];

    public function __construct(
        protected ProvinceResolver $provinceResolver,
        ?string $rulesPath = null
    ) {
        $path = $rulesPath ?? base_path('data/region_rules.json');
        if (File::exists($path)) {
            $this->regionRules = json_decode(File::get($path), true) ?? [];
        }
    }

    /**
     * Get complete weather summary for a trip in a province.
     * Uses Open-Meteo 16-day forecast if within 16 days, else monthly climate rules.
     *
     * @return array{
     *     mode: string,
     *     provider: string,
     *     attribution: string,
     *     attribution_url: string,
     *     province_code: string,
     *     daily: array<int, array{
     *         day: int,
     *         date: string,
     *         temp_max: float,
     *         temp_min: float,
     *         condition: string,
     *         has_rain: bool,
     *         precipitation_mm: float,
     *         is_outdoor_suitable: bool,
     *         warning: string|null
     *     }>,
     *     season_hazards: array<int, array<string, mixed>>,
     *     general_advice: string
     * }
     */
    public function getWeatherSummary(string $provinceCode, ?string $startDate = null, int $durationDays = 3): array
    {
        $province = $this->provinceResolver->getByCode($provinceCode);
        if (! $province) {
            return $this->getEmptyFallback($provinceCode, $startDate, $durationDays);
        }

        $start = $startDate ? Carbon::parse($startDate) : Carbon::today();
        $daysUntilTrip = Carbon::today()->diffInDays($start, false);

        // Check seasonal hazards from region rules
        $seasonHazards = $this->detectSeasonalHazards($province, $start, $durationDays);

        // If trip starts within 16 days, attempt live Open-Meteo forecast with disk cache
        if ($daysUntilTrip >= 0 && $daysUntilTrip <= 15) {
            $forecastResult = $this->fetchOpenMeteoForecast($province, $start, $durationDays);
            if ($forecastResult !== null) {
                $forecastResult['season_hazards'] = $seasonHazards;
                $forecastResult['general_advice'] = $this->generateGeneralAdvice($forecastResult['daily'], $seasonHazards, $province);
                return $forecastResult;
            }
        }

        // Otherwise (or if API fails), degrade gracefully to historical climate rules
        return $this->generateClimateRulesWeather($province, $start, $durationDays, $seasonHazards);
    }

    /**
     * Check if a specific date and province is suitable for outdoor activities.
     */
    public function isOutdoorSuitable(string $provinceCode, string $date): bool
    {
        $summary = $this->getWeatherSummary($provinceCode, $date, 1);
        $firstDay = $summary['daily'][0] ?? null;

        return $firstDay['is_outdoor_suitable'] ?? true;
    }

    /**
     * Check if forecast predicts heavy rain or storm.
     */
    public function hasHeavyRainOrStorm(string $provinceCode, string $date): bool
    {
        $summary = $this->getWeatherSummary($provinceCode, $date, 1);
        $firstDay = $summary['daily'][0] ?? null;

        if (! $firstDay) {
            return false;
        }

        return ($firstDay['precipitation_mm'] ?? 0) >= 15.0 || str_contains($firstDay['condition'] ?? '', 'dông');
    }

    /**
     * Fetch forecast from Open-Meteo API with disk caching.
     */
    protected function fetchOpenMeteoForecast(array $province, Carbon $start, int $durationDays): ?array
    {
        $code = $province['code'];
        $dateStr = $start->format('Y-m-d');
        $cacheDir = storage_path('app/weather_cache');
        File::ensureDirectoryExists($cacheDir);
        $cacheFile = "{$cacheDir}/{$code}_{$dateStr}.json";

        // Cache valid for 6 hours
        if (File::exists($cacheFile) && (time() - File::lastModified($cacheFile) < 21600)) {
            $cached = json_decode(File::get($cacheFile), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $lat = $province['centroid']['lat'];
        $lng = $province['centroid']['lng'];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'WanderlyApp/1.0 (Travel Itinerary Planner; contact@wanderly.vn)'
            ])->timeout(12)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lng,
                'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode',
                'timezone' => 'Asia/Bangkok',
                'forecast_days' => 16,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $parsed = $this->parseOpenMeteoResponse($data, $start, $durationDays, $code);

                File::put($cacheFile, json_encode($parsed, JSON_PRETTY_PRINT));
                return $parsed;
            }

            Log::warning("Open-Meteo API returned status " . $response->status() . " for {$code}");
        } catch (\Throwable $e) {
            Log::warning("Open-Meteo network failed for {$code}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Parse raw Open-Meteo JSON into structured daily forecasts.
     */
    protected function parseOpenMeteoResponse(array $data, Carbon $start, int $durationDays, string $provinceCode): array
    {
        $dailyRaw = $data['daily'] ?? [];
        $timeArray = $dailyRaw['time'] ?? [];
        $tMaxArray = $dailyRaw['temperature_2m_max'] ?? [];
        $tMinArray = $dailyRaw['temperature_2m_min'] ?? [];
        $precipArray = $dailyRaw['precipitation_sum'] ?? [];
        $wcodeArray = $dailyRaw['weathercode'] ?? [];

        $daily = [];
        for ($i = 0; $i < $durationDays; $i++) {
            $targetDate = $start->copy()->addDays($i)->format('Y-m-d');
            $idx = array_search($targetDate, $timeArray, true);

            if ($idx !== false) {
                $code = (int) ($wcodeArray[$idx] ?? 0);
                $conditionInfo = $this->interpretWmoCode($code);
                $precip = (float) ($precipArray[$idx] ?? 0.0);
                $hasRain = $precip > 2.0 || in_array($code, [51, 53, 55, 61, 63, 65, 80, 81, 82, 95, 96, 99]);
                $isOutdoor = $precip < 8.0 && ! in_array($code, [63, 65, 81, 82, 95, 96, 99]);

                $warning = null;
                if (! $isOutdoor) {
                    $warning = 'Dự báo có mưa rào hoặc dông lớn. Đề xuất ưu tiên các điểm tham quan văn hóa, bảo tàng và ẩm thực trong nhà.';
                } elseif ($hasRain) {
                    $warning = 'Có thể có mưa bay nhẹ rải rác. Nên chuẩn bị ô/dù và áo mưa mỏng.';
                }

                $daily[] = [
                    'day' => $i + 1,
                    'date' => $targetDate,
                    'temp_max' => round((float) ($tMaxArray[$idx] ?? 30.0), 1),
                    'temp_min' => round((float) ($tMinArray[$idx] ?? 23.0), 1),
                    'condition' => $conditionInfo,
                    'has_rain' => $hasRain,
                    'precipitation_mm' => $precip,
                    'is_outdoor_suitable' => $isOutdoor,
                    'warning' => $warning,
                ];
            } else {
                // Fallback for dates beyond 16 days index
                $daily[] = $this->getSyntheticDay($i + 1, $targetDate);
            }
        }

        return [
            'mode' => 'forecast',
            'provider' => 'Open-Meteo.com',
            'attribution' => self::ATTRIBUTION_TEXT,
            'attribution_url' => self::ATTRIBUTION_URL,
            'province_code' => $provinceCode,
            'daily' => $daily,
            'season_hazards' => [],
            'general_advice' => '',
        ];
    }

    /**
     * Generate fallback climate rules weather when trip is far in future or API is down.
     */
    protected function generateClimateRulesWeather(array $province, Carbon $start, int $durationDays, array $seasonHazards): array
    {
        $code = $province['code'];
        $seasonNotes = $province['season_notes'] ?? [];
        $avoidMonths = $seasonNotes['avoid_months'] ?? [];

        $daily = [];
        for ($i = 0; $i < $durationDays; $i++) {
            $curDate = $start->copy()->addDays($i);
            $month = (int) $curDate->format('n');
            $isInAvoid = in_array($month, $avoidMonths, true);

            $hasHazard = false;
            $hazardWarning = null;
            foreach ($seasonHazards as $h) {
                if ($h['force_indoor'] ?? false) {
                    $hasHazard = true;
                    $hazardWarning = $h['warning'];
                    break;
                }
            }

            $isOutdoor = ! $hasHazard && ! $isInAvoid;

            $condition = 'Thời tiết ôn hòa, tạnh ráo';
            if ($hasHazard) {
                $condition = 'Mùa mưa bão / bất lợi thời tiết';
            } elseif ($isInAvoid) {
                $condition = 'Mùa mưa rải rác theo quy luật khí hậu';
            }

            $daily[] = [
                'day' => $i + 1,
                'date' => $curDate->format('Y-m-d'),
                'temp_max' => in_array($province['region'] ?? '', ['north_mountain']) ? 22.0 : 31.0,
                'temp_min' => in_array($province['region'] ?? '', ['north_mountain']) ? 15.0 : 24.0,
                'condition' => $condition,
                'has_rain' => $hasHazard || $isInAvoid,
                'precipitation_mm' => $hasHazard ? 25.0 : ($isInAvoid ? 10.0 : 1.0),
                'is_outdoor_suitable' => $isOutdoor,
                'warning' => $hazardWarning ?? ($isInAvoid ? 'Tháng này nằm trong giai đoạn thời tiết thất thường của địa phương.' : null),
            ];
        }

        return [
            'mode' => 'climate_rules',
            'provider' => 'Open-Meteo.com (Historical Climate Norms & Region Rules)',
            'attribution' => self::ATTRIBUTION_TEXT,
            'attribution_url' => self::ATTRIBUTION_URL,
            'province_code' => $code,
            'daily' => $daily,
            'season_hazards' => $seasonHazards,
            'general_advice' => $this->generateGeneralAdvice($daily, $seasonHazards, $province),
        ];
    }

    /**
     * Detect seasonal hazards according to region rules and trip month.
     */
    protected function detectSeasonalHazards(array $province, Carbon $start, int $durationDays): array
    {
        $hazards = [];
        $rules = $this->regionRules['seasonal_hazards'] ?? [];
        $region = $province['region'] ?? '';
        $month = (int) $start->format('n');

        foreach ($rules as $key => $rule) {
            $applicableRegions = $rule['regions'] ?? [];
            $applicableMonths = $rule['months'] ?? [];

            if (in_array($region, $applicableRegions, true) && in_array($month, $applicableMonths, true)) {
                $hazards[] = [
                    'key' => $key,
                    'name' => $rule['name'],
                    'warning' => $rule['warning'],
                    'force_indoor' => $rule['force_indoor'] ?? false,
                ];
            }
        }

        return $hazards;
    }

    /**
     * Generate helpful travel advice text combining weather and season notes.
     */
    protected function generateGeneralAdvice(array $daily, array $hazards, array $province): string
    {
        $parts = [];

        // Check rain days count
        $rainDays = count(array_filter($daily, fn ($d) => $d['has_rain']));
        if ($rainDays === 0) {
            $parts[] = 'Thời tiết toàn bộ hành trình rất đẹp, nắng ráo và thuận lợi cho các hoạt động ngoài trời, tắm biển và trekking.';
        } elseif ($rainDays === count($daily)) {
            $parts[] = 'Có mưa trong các ngày của chuyến đi. Hệ thống đã tự động cân đối đưa các bảo tàng, điểm văn hóa và ẩm thực trong nhà vào khung giờ mưa.';
        } else {
            $parts[] = "Dự báo có {$rainDays} ngày có thể có mưa nhẹ. Hãy mang theo ô nhỏ gọn hoặc áo khoác chống nước nhẹ.";
        }

        foreach ($hazards as $h) {
            $parts[] = "Lưu ý mùa: " . $h['warning'];
        }

        $seasonNote = $province['season_notes']['note'] ?? null;
        if ($seasonNote) {
            $parts[] = "Đặc trưng khí hậu địa phương: {$seasonNote}";
        }

        return implode(' ', $parts);
    }

    /**
     * Map WMO Weather Interpretation Codes to Vietnamese text.
     */
    public function interpretWmoCode(int $code): string
    {
        return match ($code) {
            0 => 'Trời quang, nắng đẹp',
            1, 2 => 'Trời có mây, nắng dịu',
            3 => 'Nhiều mây',
            45, 48 => 'Có sương mù',
            51, 53, 55 => 'Mưa phùn nhẹ',
            61 => 'Mưa rào nhẹ',
            63 => 'Mưa vừa',
            65 => 'Mưa to đến rất to',
            71, 73, 75 => 'Mưa tuyết / Mưa đá',
            80, 81 => 'Mưa rào rải rác',
            82 => 'Mưa rào xối xả',
            95 => 'Dông rải rác',
            96, 99 => 'Dông bão có sét lớn',
            default => 'Thời tiết ôn hòa',
        };
    }

    protected function getSyntheticDay(int $day, string $date): array
    {
        return [
            'day' => $day,
            'date' => $date,
            'temp_max' => 30.0,
            'temp_min' => 24.0,
            'condition' => 'Trời có mây, tạnh ráo',
            'has_rain' => false,
            'precipitation_mm' => 0.0,
            'is_outdoor_suitable' => true,
            'warning' => null,
        ];
    }

    protected function getEmptyFallback(string $provinceCode, ?string $startDate, int $durationDays): array
    {
        return [
            'mode' => 'fallback',
            'provider' => 'Open-Meteo.com',
            'attribution' => self::ATTRIBUTION_TEXT,
            'attribution_url' => self::ATTRIBUTION_URL,
            'province_code' => $provinceCode,
            'daily' => [],
            'season_hazards' => [],
            'general_advice' => 'Không thể tải thông tin thời tiết. Lịch trình sẽ được lập dựa trên điều kiện tiêu chuẩn.',
        ];
    }
}
