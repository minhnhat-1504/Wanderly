<?php

namespace App\Services\Itinerary;

use Carbon\Carbon;

class ItineraryValidator
{
    public const MAX_DAILY_TRAVEL_MINUTES = 180;
    public const MAX_CONSECUTIVE_DISTANCE_KM = 60.0;
    public const MIN_ITEMS_PER_DAY = 3;
    public const MAX_ITEMS_PER_DAY = 8;

    public function __construct(
        protected PlaceRepository $placeRepository,
        protected TravelTimeService $travelTimeService
    ) {}

    /**
     * Validate an itinerary against the 9 deterministic rules.
     *
     * @param  array{
     *     province_code: string,
     *     days: array<int, array{
     *         day: int,
     *         items: array<int, array{
     *             time: string,
     *             place_id: string,
     *             activity?: string,
     *             duration_min?: int,
     *             note?: string
     *         }>
     *     }>,
     *     tips?: array<int, string>,
     *     unverified?: array<int, string>
     * }  $plan
     * @param  array{
     *     expected_days?: int,
     *     start_date?: string,
     *     month?: int,
     *     weather?: array<string, mixed>
     * }  $options
     * @return array{
     *     valid: bool,
     *     errors: array<int, string>,
     *     warnings: array<int, string>,
     *     stats: array<string, mixed>
     * }
     */
    public function validate(array $plan, array $options = []): array
    {
        $errors = [];
        $warnings = [];

        $provinceCode = strtolower(trim($plan['province_code'] ?? ''));
        if (empty($provinceCode)) {
            $errors[] = 'Mã tỉnh (province_code) không được để trống.';
            return $this->formatResult(false, $errors, $warnings, []);
        }

        $days = $plan['days'] ?? [];
        if (! is_array($days) || empty($days)) {
            $errors[] = 'Lịch trình không có dữ liệu ngày (days rỗng).';
            return $this->formatResult(false, $errors, $warnings, []);
        }

        // Option parameters
        $expectedDays = $options['expected_days'] ?? count($days);
        $travelMonth = isset($options['month']) ? (int) $options['month'] : null;
        $startDate = ! empty($options['start_date']) ? Carbon::parse($options['start_date']) : null;
        if (! $travelMonth && $startDate) {
            $travelMonth = $startDate->month;
        }

        // Weather map indexed by day number (1-based)
        $weatherByDay = [];
        if (! empty($options['weather']['daily']) && is_array($options['weather']['daily'])) {
            foreach ($options['weather']['daily'] as $w) {
                $dayNum = $w['day'] ?? null;
                if ($dayNum) {
                    $weatherByDay[$dayNum] = $w;
                }
            }
        }

        // Rule 8: Day count
        if (count($days) !== (int) $expectedDays) {
            $errors[] = "Số ngày trong lịch trình (".count($days)." ngày) không khớp với yêu cầu ({$expectedDays} ngày).";
        }

        $seenPlaceIds = [];
        $totalTripTravelMinutes = 0;
        $totalTripDistanceKm = 0.0;
        $dayStats = [];

        foreach ($days as $dayIndex => $dayData) {
            $dayNumber = $dayData['day'] ?? ($dayIndex + 1);
            $items = $dayData['items'] ?? [];

            // Rule 8: Item count per day
            $itemCount = count($items);
            if ($itemCount < self::MIN_ITEMS_PER_DAY) {
                $errors[] = "Ngày {$dayNumber} có quá ít hoạt động ({$itemCount} điểm, tối thiểu là ".self::MIN_ITEMS_PER_DAY.").";
            } elseif ($itemCount > self::MAX_ITEMS_PER_DAY) {
                $errors[] = "Ngày {$dayNumber} quá tải ({$itemCount} điểm, tối đa là ".self::MAX_ITEMS_PER_DAY.").";
            }

            $currentDate = $startDate ? $startDate->copy()->addDays($dayIndex) : null;
            $isoDayOfWeek = $currentDate ? $currentDate->isoWeekday() : null; // 1=Mon .. 7=Sun
            $dayWeather = $weatherByDay[$dayNumber] ?? null;

            $hasLunchSlot = false;
            $hasDinnerSlot = false;
            $dayTravelMinutes = 0;
            $dayDistanceKm = 0.0;
            $previousPlace = null;
            $previousEndTime = null;

            foreach ($items as $itemIdx => $item) {
                $placeId = trim($item['place_id'] ?? '');
                $itemTime = trim($item['time'] ?? '08:00');
                $duration = (int) ($item['duration_min'] ?? 60);

                // Rule 1: Place ID existence & verification in dataset
                if (empty($placeId)) {
                    $errors[] = "Ngày {$dayNumber}, hoạt động #".($itemIdx + 1).": Thiếu mã địa điểm (place_id).";
                    continue;
                }

                $place = $this->placeRepository->getById($placeId, $provinceCode);

                if (! $place) {
                    $errors[] = "Địa điểm [{$placeId}] không tồn tại trong tập dữ liệu của {$provinceCode}.";
                    continue;
                }

                if (empty($place['verified']) || $place['verified'] !== true || ($place['source'] ?? '') === 'llm_draft') {
                    $errors[] = "Địa điểm [{$place['name_vi']}] ({$placeId}) chưa được kiểm chứng (verified=false hoặc llm_draft).";
                    continue;
                }

                if (($place['province_code'] ?? '') !== $provinceCode) {
                    $errors[] = "Địa điểm [{$place['name_vi']}] ({$placeId}) thuộc {$place['province_code']}, không nằm trong {$provinceCode}.";
                    continue;
                }

                // Rule 2: No duplicates across trip
                if (isset($seenPlaceIds[$placeId])) {
                    $errors[] = "Địa điểm [{$place['name_vi']}] ({$placeId}) bị trùng lặp nhiều lần trong lịch trình.";
                }
                $seenPlaceIds[$placeId] = true;

                // Rule 3: Opening hours & closed days
                $this->checkOpeningHoursAndClosedDays(
                    $place,
                    $itemTime,
                    $duration,
                    $dayNumber,
                    $isoDayOfWeek,
                    $errors,
                    $warnings
                );

                // Rule 6: Seasonal avoid months
                if ($travelMonth) {
                    $avoidMonths = $place['avoid_months'] ?? [];
                    if (in_array($travelMonth, $avoidMonths, true)) {
                        $errors[] = "Địa điểm [{$place['name_vi']}] không thích hợp tham quan vào tháng {$travelMonth} ({$place['notes']}).";
                    }
                }

                // Rule 7: Weather safety (heavy rain / storm and outdoor)
                if ($dayWeather) {
                    $isStormOrHeavyRain = ($dayWeather['precipitation_mm'] ?? 0) >= 15.0 || in_array($dayWeather['wmo_code'] ?? 0, [95, 96, 99], true);
                    $isOutdoor = ! ($place['indoor'] ?? false);
                    if ($isStormOrHeavyRain && $isOutdoor) {
                        $errors[] = "Ngày {$dayNumber} dự báo mưa lớn/bão, địa điểm ngoài trời [{$place['name_vi']}] không an toàn.";
                    }
                }

                // Rule 9: Meal slot detection
                $category = $place['category'] ?? '';
                $isFoodCategory = in_array($category, ['food', 'cafe'], true);
                $activityText = strtolower(($item['activity'] ?? '').' '.($item['note'] ?? ''));
                $hasMealKeyword = str_contains($activityText, 'ăn') || str_contains($activityText, 'cơm') || str_contains($activityText, 'ẩm thực') || str_contains($activityText, 'bún') || str_contains($activityText, 'phở');

                $timeMinutes = $this->timeStringToMinutes($itemTime);
                if (($isFoodCategory || $hasMealKeyword) && $timeMinutes >= 660 && $timeMinutes <= 870) {
                    // 11:00 to 14:30
                    $hasLunchSlot = true;
                }
                if (($isFoodCategory || $hasMealKeyword) && $timeMinutes >= 1050 && $timeMinutes <= 1290) {
                    // 17:30 to 21:30
                    $hasDinnerSlot = true;
                }

                // Rules 4 & 5: Travel time and consecutive distance
                if ($previousPlace !== null) {
                    $travelMins = $this->travelTimeService->travel_minutes($previousPlace, $place, $provinceCode);
                    $distance = $this->travelTimeService->distance_km($previousPlace, $place);

                    $dayTravelMinutes += $travelMins;
                    $dayDistanceKm += $distance;

                    // Rule 5: Consecutive distance limit
                    if ($distance > self::MAX_CONSECUTIVE_DISTANCE_KM) {
                        $errors[] = "Khoảng cách giữa [{$previousPlace['name_vi']}] và [{$place['name_vi']}] quá xa (".round($distance, 1)." km > ".self::MAX_CONSECUTIVE_DISTANCE_KM." km).";
                    }
                }

                $previousPlace = $place;
            }

            // Rule 4: Total daily travel time <= 180 min
            if ($dayTravelMinutes > self::MAX_DAILY_TRAVEL_MINUTES) {
                $errors[] = "Tổng thời gian di chuyển Ngày {$dayNumber} quá tải ({$dayTravelMinutes} phút > ".self::MAX_DAILY_TRAVEL_MINUTES." phút).";
            }

            // Rule 9 check: Lunch and Dinner warnings
            if (! $hasLunchSlot) {
                $warnings[] = "Ngày {$dayNumber} chưa có điểm dừng ăn trưa rõ ràng (khung 11:30 - 13:30).";
            }
            if (! $hasDinnerSlot) {
                $warnings[] = "Ngày {$dayNumber} chưa có điểm dừng ăn tối rõ ràng (khung 18:00 - 20:30).";
            }

            $totalTripTravelMinutes += $dayTravelMinutes;
            $totalTripDistanceKm += $dayDistanceKm;

            $dayStats[$dayNumber] = [
                'travel_minutes' => $dayTravelMinutes,
                'distance_km' => round($dayDistanceKm, 2),
                'items_count' => $itemCount,
            ];
        }

        $isValid = empty($errors);

        return $this->formatResult($isValid, $errors, $warnings, [
            'total_places' => count($seenPlaceIds),
            'total_travel_minutes' => $totalTripTravelMinutes,
            'total_distance_km' => round($totalTripDistanceKm, 2),
            'days' => $dayStats,
        ]);
    }

    /**
     * Check opening hours and closed days for a place.
     */
    protected function checkOpeningHoursAndClosedDays(
        array $place,
        string $itemTime,
        int $durationMin,
        int $dayNumber,
        ?int $isoDayOfWeek,
        array &$errors,
        array &$warnings
    ): void {
        $placeName = $place['name_vi'] ?? 'Địa điểm';

        // Check closed days if day of week is known
        if ($isoDayOfWeek !== null && ! empty($place['closed_days'])) {
            if (in_array($isoDayOfWeek, $place['closed_days'], true)) {
                $errors[] = "Địa điểm [{$placeName}] đóng cửa vào ngày này (thứ {$isoDayOfWeek}).";
            }
        }

        $openingHours = $place['opening_hours'] ?? null;
        if (! is_array($openingHours) || empty($openingHours['open']) || empty($openingHours['close'])) {
            // Null opening hours means open 24/7 or unverified
            return;
        }

        $openMins = $this->timeStringToMinutes($openingHours['open']);
        $closeMins = $this->timeStringToMinutes($openingHours['close']);
        $visitStartMins = $this->timeStringToMinutes($itemTime);
        $visitEndMins = $visitStartMins + $durationMin;

        // If closing is before opening (e.g. 18:00 to 02:00 overnight bar/club)
        $isOvernight = $closeMins < $openMins;
        if ($isOvernight) {
            $closeMins += 1440; // add 24h
            if ($visitStartMins < $openMins && $visitStartMins < ($closeMins - 1440)) {
                $visitStartMins += 1440;
                $visitEndMins += 1440;
            }
        }

        // Tolerance window of 15 minutes
        if ($visitStartMins < ($openMins - 15) || $visitEndMins > ($closeMins + 15)) {
            $errors[] = "Thời gian ghé thăm [{$placeName}] ({$itemTime}, {$durationMin} phút) nằm ngoài giờ mở cửa ({$openingHours['open']} - {$openingHours['close']}).";
        }
    }

    /**
     * Convert "HH:MM" string to minutes from 00:00.
     */
    protected function timeStringToMinutes(string $timeStr): int
    {
        $parts = explode(':', trim($timeStr));
        $hours = (int) ($parts[0] ?? 0);
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }

    /**
     * Format validation return structure.
     */
    protected function formatResult(bool $valid, array $errors, array $warnings, array $stats): array
    {
        return [
            'valid' => $valid,
            'errors' => $errors,
            'warnings' => $warnings,
            'stats' => $stats,
        ];
    }
}
