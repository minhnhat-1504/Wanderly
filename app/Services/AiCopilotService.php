<?php

namespace App\Services;

use App\Models\Poi;
use App\Models\Trip;
use App\Models\User;
use App\Services\Itinerary\ItineraryPlannerService;
use App\Services\Itinerary\PlaceRepository;
use App\Services\Itinerary\ProvinceResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCopilotService
{
    public function __construct(
        protected CollaborationService $collaborationService,
        protected BudgetService $budgetService,
        protected ProvinceResolver $provinceResolver,
        protected ItineraryPlannerService $itineraryPlanner,
        protected PlaceRepository $placeRepository
    ) {}

    /**
     * Send messages to LLM API (Gemini or OpenAI) with fallback to smart local simulator.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $tools
     * @return array{content: string, suggested_action: ?array}
     */
    public function callLlm(array $messages, array $tools = []): array
    {
        $provider = config('ai.provider', 'gemini');

        // Check if real API key exists
        $geminiKey = config('ai.gemini.api_key');
        $openAiKey = config('ai.openai.api_key');

        if ($provider === 'gemini' && ! empty($geminiKey)) {
            try {
                $response = $this->callGeminiApi($messages, $tools);
                if ($response) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, using smart fallback: '.$e->getMessage());
            }
        } elseif ($provider === 'openai' && ! empty($openAiKey)) {
            try {
                $response = $this->callOpenAiApi($messages, $tools);
                if ($response) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI API call failed, using smart fallback: '.$e->getMessage());
            }
        }

        // Smart Local Simulation Fallback
        return $this->smartLocalSimulate($messages, $tools);
    }

    /**
     * Build rich contextual grounding data for a trip to prevent hallucination.
     *
     * @return array<string, mixed>
     */
    public function buildTripContext(Trip $trip): array
    {
        $tripDays = 3;
        if ($trip->start_date && $trip->end_date) {
            $tripDays = (int) $trip->start_date->diffInDays($trip->end_date) + 1;
        }

        $groupMood = $this->collaborationService->calculateGroupMood($trip);
        $budgetSummary = $this->budgetService->calculateBudgetStatus($trip);

        // Fetch valid POIs near trip's destination
        $pois = [];
        $destName = $trip->destination;
        if (! empty($destName)) {
            $pois = Poi::whereHas('zone.destination', function ($q) use ($destName) {
                $q->where('name', 'like', "%{$destName}%");
            })->orWhereHas('zone', function ($q) use ($destName) {
                $q->where('name', 'like', "%{$destName}%");
            })->select('id', 'name', 'category', 'typical_cost')
                ->take(15)
                ->get()
                ->toArray();
        }

        // Current trip activities count
        $activityCount = $trip->activities()->count();

        return [
            'trip_id' => $trip->id,
            'title' => $trip->title,
            'destination' => $trip->destination?->name ?? 'Vietnam',
            'duration_days' => $tripDays,
            'budget_total' => (float) ($trip->budget ?? 0),
            'budget_remaining' => (float) ($budgetSummary['remaining_budget'] ?? $trip->budget ?? 0),
            'group_pace' => $groupMood['common_pace'] ?? 'balanced',
            'top_interests' => array_column($groupMood['top_interests'] ?? [], 'tag'),
            'members_count' => $trip->members()->count(),
            'activity_count' => $activityCount,
            'available_pois' => $pois,
        ];
    }

    /**
     * Mediate conflict between members and generate balanced compromise suggestion.
     *
     * @return array{content: string, suggested_action: ?array}
     */
    public function mediateGroupConflict(Trip $trip, string $userPrompt): array
    {
        $context = $this->buildTripContext($trip);

        $systemPrompt = "You are Wanderly AI Co-pilot, an expert group travel mediator for trips in Vietnam.
Trip Context:
- Destination: {$context['destination']}
- Budget Remaining: {$context['budget_remaining']} VND
- Top Group Interests: ".implode(', ', $context['top_interests'])."
- Group Pace: {$context['group_pace']}
Available Local POIs: ".json_encode($context['available_pois'], JSON_UNESCAPED_UNICODE)."

Analyze the group conflict or request. Always return a solution that balances different member preferences.
If a suitable activity or POI matches, include a suggested action in JSON with:
action: 'create_activity'
params: { title, day, time_slot, cost, poi_id, location, description }";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        return $this->callLlm($messages, ['context' => $context, 'task' => 'mediation']);
    }

    /**
     * General travel assistant chat when user is not inside a specific trip.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{content: string, suggested_action: ?array}
     */
    public function chatGeneralTravel(string $userPrompt, array $history = []): array
    {
        // 1. Detect if the user wants an itinerary or mentions a province
        $resolved = $this->provinceResolver->resolve($userPrompt);

        // If province not mentioned in current prompt, check recent history
        if (! $resolved['resolved'] && ! empty($history)) {
            foreach (array_reverse($history) as $h) {
                $histResolved = $this->provinceResolver->resolve($h['content'] ?? '');
                if ($histResolved['resolved'] && ! empty($histResolved['province'])) {
                    $resolved = $histResolved;
                    break;
                }
            }
        }

        // Distinguish factual place/address/food query vs full itinerary request
        $isFactualQuery = preg_match('/(địa chỉ|mở cửa|đóng cửa|mấy giờ|bao nhiêu tiền|giá vé|vé vào cổng|ở đâu|có gì ngon|nằm ở đâu|nằm tại đâu)/iu', $userPrompt)
            && ! preg_match('/(lịch trình|kế hoạch|chuyến đi|hành trình|tour|\d+\s*(?:ngày|ngay|n)|(?:một|hai|ba|bốn|năm)\s*ngày)/iu', $userPrompt);

        // Check for itinerary / travel planning intent keywords
        $isItineraryIntent = ! $isFactualQuery && preg_match('/(lịch trình|kế hoạch|chuyến đi|hành trình|tour|trip|gợi ý|đi chơi|du lịch|mấy ngày|\d+\s*(?:ngày|ngay|n)|tham quan|khám phá|hai ngày|ba ngày|bốn ngày|năm ngày|một ngày)/iu', $userPrompt);

        if ($resolved['resolved'] && ! empty($resolved['province']) && $isItineraryIntent) {
            $days = $this->parseRequestedDays($userPrompt, 3);
            $budget = $this->parseRequestedBudget($userPrompt, $history);
            $pace = $this->parseRequestedPace($userPrompt);
            $interests = $this->parseRequestedInterests($userPrompt);
            $startDate = $this->parseRequestedStartDate($userPrompt);

            $options = [
                'budget' => $budget,
                'pace' => $pace,
                'interests' => $interests,
            ];
            if ($startDate) {
                $options['start_date'] = $startDate;
            }

            $province = $resolved['province'];
            $plan = $this->itineraryPlanner->plan($province['name'], $days, $options);

            if ($plan['status'] === 'success') {
                $summary = $plan['summary'];
                $budgetStr = $summary['budget_formatted'] ?? ($budget ? number_format($budget, 0, ',', '.') . ' VNĐ' : '');
                $ticketStr = $summary['total_ticket_cost_formatted'] ?? '0 VNĐ';

                $content = "Dưới đây là lịch trình {$days} ngày tại **{$province['name']}**";
                if ($budget) {
                    $content .= " phù hợp với ngân sách **" . number_format($budget, 0, ',', '.') . " VNĐ**";
                }
                if ($pace !== 'balanced') {
                    $paceDesc = $pace === 'relaxed' ? 'chill & thong thả' : 'khám phá năng động';
                    $content .= " theo phong cách **{$paceDesc}**";
                }
                $content .= " được Wanderly lập tự động từ dữ liệu đã kiểm chứng:\n\n";

                $content .= "🌤️ **Thời tiết & Lời khuyên:** {$plan['weather_summary']['general_advice']}\n";
                $content .= "📍 **Quy mô:** {$summary['total_places']} điểm đến • ⏱️ Di chuyển: ~{$summary['total_travel_minutes']} phút\n";
                if (! empty($budgetStr)) {
                    $content .= "💰 **Dự toán chi phí:** Vé tham quan: **{$ticketStr}** • Ngân sách chuyến đi: **{$budgetStr}**\n\n";
                } else {
                    $content .= "💰 **Dự toán chi phí:** Vé tham quan: **{$ticketStr}**\n\n";
                }
                $content .= "Mời bạn xem chi tiết các điểm đến bên dưới (có kèm link Google Maps và giờ mở cửa) hoặc nhấn **'Tạo chuyến đi từ lịch trình này'** để lưu thành chuyến đi thực tế!";

                return [
                    'content' => $content,
                    'suggested_action' => [
                        'type' => 'create_trip_itinerary',
                        'action' => 'create_trip_itinerary',
                        'params' => $plan,
                    ],
                ];
            }
        } elseif (! empty($resolved['clarification']) && $isItineraryIntent && ! $resolved['resolved']) {
            return [
                'content' => $resolved['clarification'],
                'suggested_action' => null,
            ];
        }

        // 2. Ground general conversational assistant with verified local place data
        $localContext = '';
        $groundingPlaces = [];
        if ($resolved['resolved'] && ! empty($resolved['province'])) {
            $groundingPlaces = $this->placeRepository->getVerifiedByProvince($resolved['province']['code']);
            if (! empty($groundingPlaces)) {
                $placeSummaries = [];
                foreach (array_slice($groundingPlaces, 0, 15) as $lp) {
                    $hours = is_array($lp['opening_hours'] ?? null) ? "{$lp['opening_hours']['open']}-{$lp['opening_hours']['close']}" : '24/7';
                    $price = isset($lp['ticket_price_vnd']) && is_numeric($lp['ticket_price_vnd']) ? ($lp['ticket_price_vnd'] == 0 ? 'Miễn phí' : number_format($lp['ticket_price_vnd'], 0, ',', '.') . ' VNĐ') : 'chưa xác minh';
                    $specialties = ! empty($lp['local_specialties']) ? ' (Đặc sản: ' . implode(', ', $lp['local_specialties']) . ')' : '';
                    $placeSummaries[] = "- {$lp['name_vi']}: {$lp['address']} [Vé: {$price}, Giờ: {$hours}]{$specialties}";
                }
                $localContext = "\n\nDỮ LIỆU ĐỊA ĐIỂM & ĐẶC SẢN THỰC TẾ ĐÃ KIỂM CHỨNG TẠI {$resolved['province']['name']} (HÃY DÙNG ĐỊA CHỈ VÀ GIÁ NÀY ĐỂ TRẢ LỜI CHÍNH XÁC):\n" . implode("\n", $placeSummaries);
            }
        }

        $systemPrompt = 'You are Wanderly AI Travel Assistant, a friendly and highly knowledgeable travel concierge specialized in tourism across the 34 provinces/cities of Vietnam according to Resolution 202/2025/QH15.
Your goals:
1. Provide personalized travel advice, destination recommendations, seasonal best times to visit, local cuisine specialties, and transportation tips.
2. NEVER invent non-existent tourist places. If the user wants a day-by-day itinerary, recommend they provide the province name and number of days (e.g., \'Lịch trình Đà Nẵng 3 ngày\').
3. NEVER use administrative unit words \'quận/huyện/thị xã\'.
4. Keep answers concise, inspiring, well-structured, and written in natural Vietnamese with helpful emojis.
5. Output your response in JSON format with:
{
  "response": "Your helpful and detailed reply in Vietnamese...",
  "suggested_destinations": ["Da Lat", "Da Nang", "Phu Quoc"] (optional array of province/city names if relevant)
}' . $localContext;

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $h) {
            $messages[] = [
                'role' => $h['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $h['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        return $this->callLlm($messages, [
            'task' => 'general_travel',
            'province' => $resolved['province'] ?? null,
            'places' => $groundingPlaces,
        ]);
    }

    /**
     * Call Google Gemini 1.5 Flash API.
     */
    protected function callGeminiApi(array $messages, array $tools): ?array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model', 'gemini-3.5-flash');
        $endpoint = config('ai.gemini.endpoint')."/{$model}:generateContent?key={$apiKey}";

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
                'temperature' => config('ai.gemini.temperature', 0.2),
                'responseMimeType' => 'application/json',
            ],
        ];

        if (! empty($systemInstruction)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $response = Http::timeout(30)->post($endpoint, $payload);

        if ($response->successful()) {
            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            return $this->parseJsonResponse($rawText);
        }

        Log::warning('Gemini API call returned non-200: '.$response->status().' - '.$response->body());

        return null;
    }

    /**
     * Call OpenAI GPT-4o-mini API.
     */
    protected function callOpenAiApi(array $messages, array $tools): ?array
    {
        $apiKey = config('ai.openai.api_key');
        $endpoint = config('ai.openai.endpoint');

        $response = Http::withToken($apiKey)->timeout(12)->post($endpoint, [
            'model' => config('ai.openai.model', 'gpt-4o-mini'),
            'messages' => $messages,
            'temperature' => config('ai.openai.temperature', 0.2),
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $rawText = $data['choices'][0]['message']['content'] ?? '';

            return $this->parseJsonResponse($rawText);
        }

        return null;
    }

    /**
     * Smart local simulator when API key is missing or network fails.
     */
    protected function smartLocalSimulate(array $messages, array $tools): array
    {
        $lastUserMsg = '';
        foreach (array_reverse($messages) as $m) {
            if ($m['role'] === 'user') {
                $lastUserMsg = $m['content'];
                break;
            }
        }

        $context = $tools['context'] ?? [];
        $pois = $context['available_pois'] ?? [];
        $selectedPoi = ! empty($pois) ? $pois[0] : null;

        // If general travel task, respond conversationally with verified destination knowledge
        if (($tools['task'] ?? '') === 'general_travel') {
            $province = $tools['province'] ?? null;
            $provName = $province['name'] ?? 'Việt Nam';
            $grounding = $tools['places'] ?? [];
            $normalized = mb_strtolower($lastUserMsg);

            // Search for specific place queries
            $matchedPlace = null;
            foreach ($grounding as $gp) {
                if (str_contains($normalized, mb_strtolower($gp['name_vi'])) || (! empty($gp['name_en']) && str_contains($normalized, mb_strtolower($gp['name_en'])))) {
                    $matchedPlace = $gp;
                    break;
                }
            }

            if ($matchedPlace) {
                $pName = $matchedPlace['name_vi'];
                $addr = $matchedPlace['address'] ?? 'Đang cập nhật';
                $price = isset($matchedPlace['ticket_price_vnd']) && is_numeric($matchedPlace['ticket_price_vnd']) ? ($matchedPlace['ticket_price_vnd'] == 0 ? 'Miễn phí' : number_format($matchedPlace['ticket_price_vnd'], 0, ',', '.') . ' VNĐ') : 'chưa xác minh';
                $hours = is_array($matchedPlace['opening_hours'] ?? null) ? "{$matchedPlace['opening_hours']['open']} - {$matchedPlace['opening_hours']['close']}" : 'Mở cửa cả ngày';
                $note = ! empty($matchedPlace['notes']) ? "\n💡 **Lưu ý:** {$matchedPlace['notes']}" : '';

                $content = "📍 **{$pName}**\n\n- 🏡 **Địa chỉ:** {$addr}\n- 🎫 **Giá vé:** {$price}\n- 🕒 **Giờ mở cửa:** {$hours}{$note}\n\nBạn có muốn mình lập lịch trình chi tiết ghé thăm {$pName} không? (Ví dụ: *'Lịch trình {$provName} 2 ngày'*).";
            } elseif (str_contains($normalized, 'món') || str_contains($normalized, 'ăn') || str_contains($normalized, 'ẩm thực') || str_contains($normalized, 'đặc sản')) {
                $foods = [];
                foreach ($grounding as $gp) {
                    if (! empty($gp['local_specialties'])) {
                        foreach ($gp['local_specialties'] as $s) {
                            $foods[] = "• **{$s}** (gợi ý tại: {$gp['name_vi']})";
                        }
                    }
                }
                $foodsList = ! empty($foods) ? implode("\n", array_slice(array_unique($foods), 0, 5)) : "• Mì Quảng, Bún chả cá, Bánh tráng cuốn thịt heo...";
                $content = "🍜 **Đặc sản ẩm thực nhất định phải thử tại {$provName}:**\n\n{$foodsList}\n\nBạn có thể nhắn *'Lịch trình {$provName} 2 ngày'* để mình tạo ngay tour khám phá ẩm thực chuẩn nhất nhé!";
            } else {
                $content = "Chào bạn! Mình là Trợ lý Du lịch Wanderly 🌟\n\nTại **{$provName}**, mình có đầy đủ dữ liệu địa điểm, ẩm thực và giá vé đã xác thực. Bạn có thể hỏi thông tin bất kỳ địa điểm nào, hoặc nhắn ví dụ: *'Lịch trình {$provName} 2 ngày ngân sách 3 triệu'* để mình lên lịch trình tối ưu ngay nhé!";
            }

            return [
                'content' => $content,
                'suggested_action' => null,
            ];
        }

        // Mediation task fallback
        $title = $selectedPoi['name'] ?? 'Cafe & Trekking Harmony Spot';
        $location = $selectedPoi['name'] ?? ($context['destination'] ?? 'Vietnam');
        $cost = (float) ($selectedPoi['typical_cost'] ?? 150000);
        $poiId = $selectedPoi['id'] ?? null;

        // Custom synthesis based on keywords
        if (str_contains(mb_strtolower($lastUserMsg), 'trekking') && str_contains(mb_strtolower($lastUserMsg), 'cafe')) {
            $title = 'Hòa giải: Cafe View Hẻm Tu Sản & Trekking nhẹ';
            $content = 'Dựa trên sở thích của các thành viên, AI đề xuất phương án dung hòa: Ghé quán cafe ngắm cảnh đèo/hẻm núi. Nhóm thích cafe có thể thong thả chụp ảnh, trong khi nhóm thích vận động có thể dạo bộ khám phá cung đường vách đá 30 phút xung quanh.';
        } elseif (str_contains(mb_strtolower($lastUserMsg), 'mưa') || str_contains(mb_strtolower($lastUserMsg), 'weather') || str_contains(mb_strtolower($lastUserMsg), 'thời tiết')) {
            $title = 'Bảo tàng & Trải nghiệm Ẩm thực trong nhà';
            $content = 'AI nhận thấy dự báo thời tiết có thể có mưa rào. Đề xuất chuyển các hoạt động ngoài trời sang địa điểm trong nhà có trải nghiệm văn hóa và ẩm thực ấm cúng.';
        } else {
            $content = "AI đã phân tích ngữ cảnh chuyến đi và gợi ý hoạt động phù hợp với sở thích tập thể ({$title}), vừa vặn ngân sách còn lại.";
        }

        return [
            'content' => $content,
            'suggested_action' => [
                'type' => 'create_activity',
                'params' => [
                    'title' => $title,
                    'day' => 1,
                    'time_slot' => 'afternoon',
                    'cost' => $cost,
                    'location' => $location,
                    'poi_id' => $poiId,
                    'description' => 'Hoạt động do Wanderly AI Co-pilot đề xuất để tối ưu trải nghiệm nhóm.',
                ],
            ],
        ];
    }

    /**
     * Parse JSON from AI text response.
     */
    protected function parseJsonResponse(string $text): array
    {
        $trimmed = trim($text);
        if (str_starts_with($trimmed, '```json')) {
            $trimmed = preg_replace('/^```json\s*/', '', $trimmed);
            $trimmed = preg_replace('/\s*```$/', '', $trimmed);
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            $content = $decoded['content'] ?? $decoded['response'] ?? $decoded['mediation_solution'] ?? $decoded['recommendation'] ?? $decoded['solution'] ?? $decoded['message'] ?? $text;

            $suggestedAction = $decoded['suggested_action'] ?? null;
            if (! $suggestedAction && isset($decoded['action'])) {
                $suggestedAction = [
                    'type' => $decoded['action'],
                    'params' => $decoded['params'] ?? [],
                ];
            }

            return [
                'content' => (string) $content,
                'suggested_action' => $suggestedAction,
            ];
        }

        return [
            'content' => $text,
            'suggested_action' => null,
        ];
    }

    /**
     * Parse requested itinerary duration (in days) from user prompt.
     * Supports digits (2 ngày, 2n1đ, 3n) and Vietnamese number words (một, hai, ba, bốn, năm ngày).
     */
    public function parseRequestedDays(string $prompt, int $default = 3): int
    {
        $normalized = mb_strtolower(trim($prompt));

        // 1. Vietnamese number words: "hai ngày", "ba ngày", "bốn ngày", "năm ngày", "một ngày"
        $wordMap = [
            'một' => 1, 'mot' => 1, 'nhất' => 1,
            'hai' => 2, 'nhị' => 2,
            'ba' => 3, 'tam' => 3,
            'bốn' => 4, 'bon' => 4, 'tư' => 4, 'tu' => 4,
            'năm' => 5, 'nam' => 5,
        ];

        foreach ($wordMap as $word => $num) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\s*(?:ngày|ngay|hôm|hom)\b/iu', $normalized)) {
                return $num;
            }
        }

        // 2. Formats like "2n1đ", "3n2đ", "2n", "2d", "2 ngày 1 đêm", "2 ngày", "2 ngay"
        if (preg_match('/(\d+)\s*(?:n\s*\d*\s*(?:đ|d)?|ngày|ngay|hôm|hom|day|days)/iu', $normalized, $matches)) {
            return max(1, min(5, (int) $matches[1]));
        }

        // 3. Formats like "lịch trình 2", "chuyến đi 3"
        if (preg_match('/(?:lịch trình|chuyến đi|tour|kế hoạch)\s*(\d+)/iu', $normalized, $matches)) {
            return max(1, min(5, (int) $matches[1]));
        }

        return $default;
    }

    /**
     * Parse requested budget in VND from user prompt or recent history.
     * Examples: "5 triệu", "3tr", "4tr5", "500k", "3.000.000đ", "ngân sách 2tr".
     */
    public function parseRequestedBudget(string $prompt, array $history = []): ?float
    {
        $checkText = function (string $text): ?float {
            $normalized = mb_strtolower(trim($text));

            // 1. Patterns like "4tr5", "2tr5", "1tr2", "4củ5"
            if (preg_match('/(\d+)\s*(?:tr|củ|cu)\s*(\d+)\b/iu', $normalized, $matches)) {
                $main = (int) $matches[1];
                $fraction = (int) $matches[2];
                return (float) (($main * 1000000) + ($fraction * 100000));
            }

            // 2. Patterns like "5.5 triệu", "4,5 triệu", "5 triệu", "5tr", "5 củ", "5m"
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:triệu|trieu|tr\b|củ\b|cu\b|m\b)/iu', $normalized, $matches)) {
                $val = (float) str_replace(',', '.', $matches[1]);
                return $val * 1000000;
            }

            // 3. Patterns like "500k", "800k", "500 nghìn", "800 ngàn"
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:k\b|nghìn|nghin|ngàn|ngan)/iu', $normalized, $matches)) {
                $val = (float) str_replace(',', '.', $matches[1]);
                return $val * 1000;
            }

            // 4. Formatted currency numbers like "5.000.000", "5,000,000", "3000000"
            if (preg_match('/(?:\b(?:ngân sách|kinh phí|budget|chi phí|tầm|khoảng|giá)\s*[:=]?\s*)?(\d{1,3}(?:[.,]\d{3}){1,3}|\d{6,9})\s*(?:đ|vnd|vnđ|đồng|dong)?\b/iu', $normalized, $matches)) {
                $clean = preg_replace('/[^\d]/', '', $matches[1]);
                if (! empty($clean) && (float) $clean >= 50000) {
                    return (float) $clean;
                }
            }

            return null;
        };

        // Check current prompt
        $budget = $checkText($prompt);
        if ($budget !== null) {
            return $budget;
        }

        // Fallback: check history
        if (! empty($history)) {
            foreach (array_reverse($history) as $h) {
                if (($h['role'] ?? '') === 'user' || ($h['role'] ?? '') === 'model') {
                    $histBudget = $checkText($h['content'] ?? '');
                    if ($histBudget !== null) {
                        return $histBudget;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse requested travel pace from user prompt.
     * Returns: 'relaxed' | 'fast' | 'balanced'
     */
    public function parseRequestedPace(string $prompt): string
    {
        $normalized = mb_strtolower($prompt);
        if (preg_match('/(thong thả|chill|nhẹ nhàng|nghỉ dưỡng|ít điểm|chậm rãi|không vội|thư thái)/iu', $normalized)) {
            return 'relaxed';
        }
        if (preg_match('/(khám phá nhiều|dày đặc|nhiều điểm|tối đa|năng động|chạy show|check-in nhiều|dày)/iu', $normalized)) {
            return 'fast';
        }
        return 'balanced';
    }

    /**
     * Parse requested interests/categories from user prompt.
     * Returns array of categories: ['food', 'cafe', 'beach', 'nature', 'culture', 'entertainment', 'shopping']
     */
    public function parseRequestedInterests(string $prompt): array
    {
        $normalized = mb_strtolower($prompt);
        $interests = [];

        if (preg_match('/(ẩm thực|ăn uống|món ngon|đặc sản|quán ăn|ăn vặt)/iu', $normalized)) {
            $interests[] = 'food';
        }
        if (preg_match('/(cafe|cà phê|sống ảo|view đẹp|trà chiều)/iu', $normalized)) {
            $interests[] = 'cafe';
        }
        if (preg_match('/(biển|tắm biển|bãi biển|hải sản|lặn biển)/iu', $normalized)) {
            $interests[] = 'beach';
        }
        if (preg_match('/(thiên nhiên|núi|rừng|thác|trekking|hoang sơ|ngắm cảnh)/iu', $normalized)) {
            $interests[] = 'nature';
        }
        if (preg_match('/(văn hóa|lịch sử|bảo tàng|chùa|di tích|phố cổ)/iu', $normalized)) {
            $interests[] = 'culture';
        }
        if (preg_match('/(vui chơi|giải trí|công viên|chợ đêm|quầy bar)/iu', $normalized)) {
            $interests[] = 'entertainment';
        }
        if (preg_match('/(mua sắm|chợ|quà lưu niệm)/iu', $normalized)) {
            $interests[] = 'shopping';
        }

        return array_unique($interests);
    }

    /**
     * Parse requested departure/start date from user prompt.
     */
    public function parseRequestedStartDate(string $prompt): ?string
    {
        $normalized = mb_strtolower($prompt);

        if (preg_match('/(ngày mai|mai|hôm sau)/iu', $normalized)) {
            return Carbon::tomorrow()->toDateString();
        }

        if (preg_match('/(cuối tuần|thứ 7|chủ nhật)/iu', $normalized)) {
            return Carbon::now()->next(Carbon::SATURDAY)->toDateString();
        }

        // Formats like "ngày 15/11", "15-11", "20/12/2026"
        if (preg_match('/(?:ngày\s*)?(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{4}))?/iu', $normalized, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = ! empty($matches[3]) ? (int) $matches[3] : Carbon::now()->year;
            try {
                return Carbon::createFromDate($year, $month, $day)->toDateString();
            } catch (\Throwable $e) {
                // Ignore invalid date
            }
        }

        return null;
    }
}
