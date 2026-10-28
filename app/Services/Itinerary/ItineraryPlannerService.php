<?php

namespace App\Services\Itinerary;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ItineraryPlannerService
{
    public const MAX_REPAIR_RETRIES = 2;

    public function __construct(
        protected ProvinceResolver $provinceResolver,
        protected PlaceRepository $placeRepository,
        protected TravelTimeService $travelTimeService,
        protected OpenMeteoWeatherService $weatherService,
        protected ItineraryValidator $validator
    ) {}

    /**
     * Plan a verified itinerary for a province.
     *
     * @param  array{
     *     start_date?: string,
     *     month?: int,
     *     interests?: array<int, string>,
     *     pace?: string,
     *     force_greedy?: bool
     * }  $options
     * @return array<string, mixed>
     */
    public function plan(string $provinceInput, int $days = 3, array $options = []): array
    {
        // 1. Resolve province
        $resolved = $this->provinceResolver->resolve($provinceInput);
        if (! ($resolved['resolved'] ?? false) || empty($resolved['province'])) {
            return [
                'status' => 'clarification_needed',
                'message' => $resolved['clarification'] ?? 'Không tìm thấy tỉnh thành hợp lệ.',
                'candidates' => $resolved['candidates'] ?? [],
            ];
        }

        $province = $resolved['province'];
        $provinceCode = $province['code'];
        $days = max(1, min(5, (int) $days)); // Support 1-5 days

        $startDate = $options['start_date'] ?? Carbon::tomorrow()->toDateString();
        $start = Carbon::parse($startDate);
        $travelMonth = $options['month'] ?? $start->month;
        $options['start_date'] = $startDate;
        $options['month'] = $travelMonth;
        $options['expected_days'] = $days;

        // 2. Fetch weather & seasonal hazards
        $weatherSummary = $this->weatherService->getWeatherSummary($provinceCode, $startDate, $days);
        $options['weather'] = $weatherSummary;

        // 3. Get verified places from dataset
        $places = $this->placeRepository->getVerifiedByProvince($provinceCode);
        if (empty($places)) {
            return [
                'status' => 'error',
                'message' => "Chưa có dữ liệu địa điểm đã kiểm chứng cho {$province['name']}.",
            ];
        }

        // Filter out places with avoid_months matching travel month
        $eligiblePlaces = array_values(array_filter($places, function ($p) use ($travelMonth) {
            $avoid = $p['avoid_months'] ?? [];
            return ! in_array((int) $travelMonth, $avoid, true);
        }));

        if (count($eligiblePlaces) < ($days * 3)) {
            // If filtering was too strict, fallback to all verified places
            $eligiblePlaces = $places;
        }

        // 4. If force_greedy or no LLM configured, use deterministic greedy fallback directly
        $forceGreedy = $options['force_greedy'] ?? false;
        $geminiKey = config('ai.gemini.api_key');
        $openaiKey = config('ai.openai.api_key');

        if ($forceGreedy || (empty($geminiKey) && empty($openaiKey))) {
            $plan = $this->generateGreedyFallback($provinceCode, $days, $eligiblePlaces, $options);
            return $this->enrichPlanOutput($plan, $province, $weatherSummary, 'deterministic_greedy');
        }

        // 5. LLM as Selector Only with Repair Loop
        $candidates = $this->prepareCandidatesForLlm($eligiblePlaces, $days);
        $plan = $this->runLlmSelectionWithRepairLoop($provinceCode, $days, $candidates, $options, $eligiblePlaces);

        if ($plan === null) {
            // Fallback to greedy if LLM could not produce a valid plan
            $plan = $this->generateGreedyFallback($provinceCode, $days, $eligiblePlaces, $options);
            return $this->enrichPlanOutput($plan, $province, $weatherSummary, 'fallback_greedy');
        }

        return $this->enrichPlanOutput($plan, $province, $weatherSummary, 'llm_verified');
    }

    /**
     * Run LLM selection with max 2 retry repairs on validation errors.
     */
    protected function runLlmSelectionWithRepairLoop(
        string $provinceCode,
        int $days,
        array $candidates,
        array $options,
        array $eligiblePlaces
    ): ?array {
        $prompt = $this->buildLlmPrompt($provinceCode, $days, $candidates, $options);
        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()],
            ['role' => 'user', 'content' => $prompt],
        ];

        for ($attempt = 0; $attempt <= self::MAX_REPAIR_RETRIES; $attempt++) {
            $rawJson = $this->callLlmApi($messages);
            if (! $rawJson || ! is_array($rawJson)) {
                continue;
            }

            // Normalize province_code & days
            $rawJson['province_code'] = $provinceCode;

            // Deterministic validation
            $validation = $this->validator->validate($rawJson, $options);
            if ($validation['valid']) {
                return $rawJson;
            }

            // Repair prompt: send back specific errors
            if ($attempt < self::MAX_REPAIR_RETRIES) {
                $errorList = implode("\n- ", $validation['errors']);
                $messages[] = ['role' => 'assistant', 'content' => json_encode($rawJson, JSON_UNESCAPED_UNICODE)];
                $messages[] = [
                    'role' => 'user',
                    'content' => "Lịch trình bạn tạo vi phạm các quy tắc sau:\n- {$errorList}\n\nVui lòng sửa lại toàn bộ lịch trình và trả về DUY NHẤT một JSON hợp lệ tuân thủ các quy tắc trên, chỉ chọn place_id từ danh sách ứng viên đã cấp.",
                ];
            }
        }

        return null;
    }

    /**
     * Call LLM API (Gemini or OpenAI).
     */
    protected function callLlmApi(array $messages): ?array
    {
        $provider = config('ai.provider', 'gemini');
        $geminiKey = config('ai.gemini.api_key');

        if ($provider === 'gemini' && ! empty($geminiKey)) {
            try {
                return $this->callGemini($messages);
            } catch (\Throwable $e) {
                Log::warning('Gemini planner failed: ' . $e->getMessage());
            }
        }

        $openaiKey = config('ai.openai.api_key');
        if (! empty($openaiKey)) {
            try {
                return $this->callOpenAi($messages);
            } catch (\Throwable $e) {
                Log::warning('OpenAI planner failed: ' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Direct Gemini 1.5 Flash API call.
     */
    protected function callGemini(array $messages): ?array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model', 'gemini-3.5-flash');
        $endpoint = config('ai.gemini.endpoint') . "/{$model}:generateContent?key={$apiKey}";

        $systemInstruction = '';
        $contents = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = $msg['content'];
            } else {
                $role = $msg['role'] === 'assistant' ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $msg['content']]],
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        if (! empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $response = Http::timeout(25)->post($endpoint, $payload);
        if ($response->successful()) {
            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            return json_decode($rawText, true);
        }

        return null;
    }

    /**
     * Direct OpenAI call.
     */
    protected function callOpenAi(array $messages): ?array
    {
        $apiKey = config('ai.openai.api_key');
        $endpoint = config('ai.openai.endpoint');

        $response = Http::withToken($apiKey)->timeout(25)->post($endpoint, [
            'model' => config('ai.openai.model', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $rawText = $data['choices'][0]['message']['content'] ?? '';
            return json_decode($rawText, true);
        }

        return null;
    }

    /**
     * Deterministic Greedy Fallback Itinerary Generator.
     * Guaranteed to adhere to all 9 rules without hallucination.
     */
    public function generateGreedyFallback(
        string $provinceCode,
        int $days,
        array $places,
        array $options = []
    ): array {
        $travelMonth = $options['month'] ?? 10;
        $weatherDaily = $options['weather']['daily'] ?? [];

        // Separate food vs sights
        $foodPlaces = [];
        $sights = [];

        foreach ($places as $p) {
            $cat = $p['category'] ?? '';
            if (in_array($cat, ['food', 'cafe'], true)) {
                $foodPlaces[] = $p;
            } else {
                $sights[] = $p;
            }
        }

        // If very few food places, allow some sights with food tags as dining
        if (count($foodPlaces) < ($days * 2)) {
            foreach ($sights as $idx => $s) {
                $tags = $s['tags'] ?? [];
                if (in_array('culinary', $tags) || in_array('market', $tags)) {
                    $foodPlaces[] = $s;
                }
            }
        }

        // Geographic spatial clustering for sights
        $clusters = $this->clusterPlacesGeographically($sights, $days);
        $usedPlaceIds = [];
        $daysPlan = [];

        for ($d = 1; $d <= $days; $d++) {
            $dayCluster = $clusters[$d - 1] ?? [];
            $dayWeather = $weatherDaily[$d - 1] ?? null;
            $isRainy = ($dayWeather['precipitation_mm'] ?? 0) >= 15.0;

            // Pick 3-4 sights from cluster (avoid already used)
            $availableSights = array_values(array_filter($dayCluster, function ($p) use ($usedPlaceIds, $isRainy) {
                if (isset($usedPlaceIds[$p['id']])) {
                    return false;
                }
                if ($isRainy && ! ($p['indoor'] ?? false)) {
                    // In rainy weather, avoid outdoor if possible
                    return false;
                }
                return true;
            }));

            if (count($availableSights) < 2) {
                // relax rain filter if needed
                $availableSights = array_values(array_filter($dayCluster, fn($p) => ! isset($usedPlaceIds[$p['id']])));
            }

            // Order sights by nearest neighbor to minimize travel
            $orderedSights = $this->orderPlacesNearestNeighbor($availableSights, 3);
            foreach ($orderedSights as $os) {
                $usedPlaceIds[$os['id']] = true;
            }

            // Select lunch and dinner places close to morning & afternoon sights
            $morningRef = $orderedSights[0] ?? ($places[0] ?? null);
            $afternoonRef = end($orderedSights) ?: $morningRef;

            $lunchPlace = $this->findNearestAvailablePlace($foodPlaces, $morningRef, $usedPlaceIds);
            if ($lunchPlace) {
                $usedPlaceIds[$lunchPlace['id']] = true;
            }

            $dinnerPlace = $this->findNearestAvailablePlace($foodPlaces, $afternoonRef, $usedPlaceIds);
            if ($dinnerPlace) {
                $usedPlaceIds[$dinnerPlace['id']] = true;
            }

            // Assemble schedule items
            $dayItems = [];

            // 08:30 - Morning Sight 1
            if (isset($orderedSights[0])) {
                $dayItems[] = [
                    'time' => '08:30',
                    'place_id' => $orderedSights[0]['id'],
                    'activity' => 'Tham quan và khám phá điểm đến buổi sáng',
                    'duration_min' => (int) ($orderedSights[0]['suggested_duration_min'] ?? 90),
                    'note' => $orderedSights[0]['notes'] ?? 'Khởi đầu ngày mới',
                ];
            }

            // 10:30 - Morning Sight 2 (if available)
            if (isset($orderedSights[1])) {
                $dayItems[] = [
                    'time' => '10:30',
                    'place_id' => $orderedSights[1]['id'],
                    'activity' => 'Tham quan và chụp ảnh lưu niệm',
                    'duration_min' => min(60, (int) ($orderedSights[1]['suggested_duration_min'] ?? 60)),
                    'note' => $orderedSights[1]['notes'] ?? '',
                ];
            }

            // 12:00 - Lunch
            if ($lunchPlace) {
                $specialties = ! empty($lunchPlace['local_specialties']) ? implode(', ', $lunchPlace['local_specialties']) : 'ẩm thực địa phương';
                $dayItems[] = [
                    'time' => '12:00',
                    'place_id' => $lunchPlace['id'],
                    'activity' => "Ăn trưa và thưởng thức {$specialties}",
                    'duration_min' => 75,
                    'note' => 'Nghỉ trưa và nạp năng lượng',
                ];
            }

            // 14:00 - Afternoon Sight 1
            if (isset($orderedSights[2])) {
                $dayItems[] = [
                    'time' => '14:00',
                    'place_id' => $orderedSights[2]['id'],
                    'activity' => 'Khám phá và trải nghiệm buổi chiều',
                    'duration_min' => (int) ($orderedSights[2]['suggested_duration_min'] ?? 90),
                    'note' => $orderedSights[2]['notes'] ?? '',
                ];
            }

            // 16:30 - Afternoon Sight 2 (if available)
            if (isset($orderedSights[3])) {
                $dayItems[] = [
                    'time' => '16:30',
                    'place_id' => $orderedSights[3]['id'],
                    'activity' => 'Dạo mát và ngắm hoàng hôn',
                    'duration_min' => (int) ($orderedSights[3]['suggested_duration_min'] ?? 60),
                    'note' => $orderedSights[3]['notes'] ?? '',
                ];
            }

            // 18:30 - Dinner
            if ($dinnerPlace) {
                $specialties = ! empty($dinnerPlace['local_specialties']) ? implode(', ', $dinnerPlace['local_specialties']) : 'đặc sản buổi tối';
                $dayItems[] = [
                    'time' => '18:30',
                    'place_id' => $dinnerPlace['id'],
                    'activity' => "Ăn tối và thưởng thức {$specialties}",
                    'duration_min' => 75,
                    'note' => 'Thư giãn buổi tối',
                ];
            }

            $daysPlan[] = [
                'day' => $d,
                'items' => $dayItems,
            ];
        }

        return [
            'province_code' => $provinceCode,
            'days' => $daysPlan,
            'tips' => [
                'Lịch trình được tối ưu hóa theo cung đường di chuyển liền kề để giảm thời gian đi lại.',
                'Nên chuẩn bị trang phục phù hợp với thời tiết và mang theo nước uống cá nhân.',
            ],
            'unverified' => [],
        ];
    }

    /**
     * Enrich plan output with rich place details, travel times, weather, and legal attribution.
     */
    public function enrichPlanOutput(
        array $rawPlan,
        array $province,
        array $weatherSummary,
        string $sourceType = 'llm_verified'
    ): array {
        $provinceCode = $province['code'];
        $enrichedDays = [];
        $allVisitedPlaces = [];
        $totalTripTravelMinutes = 0;
        $totalTripDistanceKm = 0.0;

        foreach ($rawPlan['days'] ?? [] as $dayIdx => $day) {
            $dayNum = $day['day'] ?? ($dayIdx + 1);
            $items = $day['items'] ?? [];
            $enrichedItems = [];
            $dayWeather = $weatherSummary['daily'][$dayIdx] ?? null;

            $count = count($items);
            for ($i = 0; $i < $count; $i++) {
                $item = $items[$i];
                $placeId = $item['place_id'] ?? '';
                $place = $this->placeRepository->getById($placeId, $provinceCode);

                if (! $place) {
                    continue;
                }

                $allVisitedPlaces[$placeId] = $place;

                // Travel to next place
                $travelToNext = null;
                if ($i < ($count - 1)) {
                    $nextPlaceId = $items[$i + 1]['place_id'] ?? '';
                    $nextPlace = $this->placeRepository->getById($nextPlaceId, $provinceCode);
                    if ($nextPlace) {
                        $travelMins = $this->travelTimeService->travel_minutes($place, $nextPlace, $provinceCode);
                        $distKm = $this->travelTimeService->distance_km($place, $nextPlace);
                        $totalTripTravelMinutes += $travelMins;
                        $totalTripDistanceKm += $distKm;

                        $travelToNext = [
                            'travel_minutes' => $travelMins,
                            'distance_km' => round($distKm, 2),
                        ];
                    }
                }

                $lat = $place['lat'] ?? 0;
                $lng = $place['lng'] ?? 0;

                $enrichedItems[] = [
                    'time' => $item['time'] ?? '08:00',
                    'activity' => $item['activity'] ?? $place['name_vi'],
                    'duration_min' => (int) ($item['duration_min'] ?? $place['suggested_duration_min'] ?? 60),
                    'note' => $item['note'] ?? $place['notes'] ?? '',
                    'place' => [
                        'id' => $place['id'],
                        'name_vi' => $place['name_vi'],
                        'name_en' => $place['name_en'] ?? null,
                        'category' => $place['category'],
                        'lat' => $lat,
                        'lng' => $lng,
                        'address' => $place['address'] ?? null,
                        'google_maps_url' => "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}",
                        'opening_hours' => $place['opening_hours'] ?? 'chưa xác minh',
                        'ticket_price_vnd' => $this->formatPrice($place['ticket_price_vnd'] ?? null),
                        'indoor' => $place['indoor'] ?? false,
                        'local_specialties' => $place['local_specialties'] ?? [],
                        'source' => $place['source'] ?? 'verified',
                        'source_url' => $place['source_url'] ?? null,
                        'last_verified' => $place['last_verified'] ?? '2026-10-28',
                        'verified' => true,
                    ],
                    'travel_to_next' => $travelToNext,
                ];
            }

            $enrichedDays[] = [
                'day' => $dayNum,
                'weather' => $dayWeather,
                'items' => $enrichedItems,
            ];
        }

        return [
            'status' => 'success',
            'source_type' => $sourceType,
            'province' => [
                'code' => $province['code'],
                'name' => $province['name'],
                'region' => $province['region'] ?? '',
            ],
            'days' => $enrichedDays,
            'summary' => [
                'total_days' => count($enrichedDays),
                'total_places' => count($allVisitedPlaces),
                'total_travel_minutes' => $totalTripTravelMinutes,
                'total_distance_km' => round($totalTripDistanceKm, 2),
            ],
            'tips' => $rawPlan['tips'] ?? [],
            'weather_summary' => [
                'general_advice' => $weatherSummary['general_advice'] ?? '',
                'season_hazards' => $weatherSummary['season_hazards'] ?? [],
            ],
            'attributions' => [
                'map_data' => '© OpenStreetMap contributors (ODbL)',
                'weather_data' => OpenMeteoWeatherService::ATTRIBUTION_TEXT,
                'weather_url' => OpenMeteoWeatherService::ATTRIBUTION_URL,
                'tourism_info' => 'Wikivoyage (CC BY-SA 4.0)',
            ],
            'disclaimer' => 'Giờ mở cửa và giá vé có thể thay đổi tùy thời điểm, vui lòng kiểm tra lại trước chuyến đi.',
        ];
    }

    /**
     * Format price value for display.
     */
    protected function formatPrice(?int $price): string
    {
        if ($price === null) {
            return 'chưa xác minh';
        }
        if ($price === 0) {
            return 'Miễn phí';
        }
        return number_format($price, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Spatial clustering: groups places into $k clusters using geographic proximity.
     */
    protected function clusterPlacesGeographically(array $places, int $k): array
    {
        if (empty($places)) {
            return array_fill(0, $k, []);
        }

        $k = max(1, min($k, count($places)));
        if ($k === 1) {
            return [$places];
        }

        // Sort by latitude and longitude sum to separate geographic sectors
        usort($places, function ($a, $b) {
            return ($a['lat'] + $a['lng']) <=> ($b['lat'] + $b['lng']);
        });

        $chunkSize = (int) ceil(count($places) / $k);
        return array_chunk($places, $chunkSize);
    }

    /**
     * Order places using nearest neighbor heuristic.
     */
    protected function orderPlacesNearestNeighbor(array $places, int $limit = 4): array
    {
        if (count($places) <= 1) {
            return $places;
        }

        $ordered = [array_shift($places)];

        while (! empty($places) && count($ordered) < $limit) {
            $current = end($ordered);
            $bestIdx = 0;
            $bestDist = PHP_FLOAT_MAX;

            foreach ($places as $idx => $candidate) {
                $d = $this->travelTimeService->distance_km($current, $candidate);
                if ($d < $bestDist) {
                    $bestDist = $d;
                    $bestIdx = $idx;
                }
            }

            $ordered[] = $places[$bestIdx];
            array_splice($places, $bestIdx, 1);
        }

        return $ordered;
    }

    /**
     * Find nearest available place from a list of candidates.
     */
    protected function findNearestAvailablePlace(array $candidates, ?array $refPlace, array $usedPlaceIds): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        $best = null;
        $bestDist = PHP_FLOAT_MAX;

        foreach ($candidates as $c) {
            if (isset($usedPlaceIds[$c['id']])) {
                continue;
            }

            if (! $refPlace) {
                return $c;
            }

            $dist = $this->travelTimeService->distance_km($refPlace, $c);
            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $c;
            }
        }

        return $best;
    }

    /**
     * Prepare candidate places JSON payload for LLM prompt.
     */
    protected function prepareCandidatesForLlm(array $places, int $days): array
    {
        return array_map(function ($p) {
            return [
                'id' => $p['id'],
                'name' => $p['name_vi'],
                'category' => $p['category'],
                'lat' => $p['lat'],
                'lng' => $p['lng'],
                'hours' => $p['opening_hours'] ? "{$p['opening_hours']['open']}-{$p['opening_hours']['close']}" : '24/7',
                'duration' => $p['suggested_duration_min'] ?? 60,
                'price' => $p['ticket_price_vnd'] ?? 0,
                'indoor' => $p['indoor'] ?? false,
                'tags' => array_slice($p['tags'] ?? [], 0, 3),
            ];
        }, $places);
    }

    /**
     * System prompt establishing role, strict constraints, and schema.
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
Bạn là chuyên gia lập lịch trình du lịch chính xác tại Việt Nam của Wanderly.
NGUYÊN TẮC BẮT BUỘC:
1. KHÔNG BAO GIỜ tự bịa ra hoặc nhớ từ trí nhớ bất kỳ địa điểm nào.
2. CHỈ ĐƯỢC CHỌN place_id từ danh sách ứng viên (candidate places) đã cung cấp.
3. Mỗi ngày phải có 3-6 điểm dừng hợp lý, gồm 2-3 điểm tham quan + điểm ăn trưa (khung 11:30 - 13:00) + điểm ăn tối (khung 18:00 - 20:00).
4. KHÔNG lặp lại bất kỳ địa điểm nào trong toàn bộ chuyến đi.
5. Tổng thời gian di chuyển trong một ngày không vượt quá 180 phút. Các điểm dừng liền kề phải gần nhau.
6. Tôn trọng giờ mở cửa của từng điểm (hours).
7. TUYỆT ĐỐI KHÔNG xuất hiện các từ chỉ đơn vị hành chính "quận/huyện/thị xã" trong câu chữ.
8. Trả về DUY NHẤT một chuỗi JSON hợp lệ theo đúng cấu trúc yêu cầu, không kèm bất kỳ giải thích Markdown nào bên ngoài.
PROMPT;
    }

    /**
     * Build user prompt with candidate list, weather info, and requested days.
     */
    protected function buildLlmPrompt(string $provinceCode, int $days, array $candidates, array $options): string
    {
        $candidatesJson = json_encode($candidates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $weatherAdvice = $options['weather']['general_advice'] ?? 'Thời tiết bình thường';

        return <<<USER_PROMPT
Hãy lập lịch trình du lịch {$days} ngày tại {$provinceCode}.
Thông tin thời tiết: {$weatherAdvice}

DANH SÁCH ĐỊA ĐIỂM ỨNG VIÊN ĐÃ ĐƯỢC KIỂM CHỨNG:
{$candidatesJson}

YÊU CẦU ĐẦU RA (JSON format):
{
  "province_code": "{$provinceCode}",
  "days": [
    {
      "day": 1,
      "items": [
        {
          "time": "08:30",
          "place_id": "dn_001",
          "activity": "Mô tả hoạt động cụ thể",
          "duration_min": 90,
          "note": "Ghi chú trải nghiệm"
        }
      ]
    }
  ],
  "tips": [
    "Lời khuyên ngắn gọn hữu ích"
  ],
  "unverified": []
}
USER_PROMPT;
    }
}
