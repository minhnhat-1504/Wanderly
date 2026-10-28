<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiCopilotService
{
    public function __construct(
        protected CollaborationService $collaborationService,
        protected BudgetService $budgetService
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
                Log::warning('Gemini API call failed, using smart fallback: ' . $e->getMessage());
            }
        } elseif ($provider === 'openai' && ! empty($openAiKey)) {
            try {
                $response = $this->callOpenAiApi($messages, $tools);
                if ($response) {
                    return $response;
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI API call failed, using smart fallback: ' . $e->getMessage());
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
            $pois = \App\Models\Poi::whereHas('zone.destination', function ($q) use ($destName) {
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
- Top Group Interests: " . implode(', ', $context['top_interests']) . "
- Group Pace: {$context['group_pace']}
Available Local POIs: " . json_encode($context['available_pois'], JSON_UNESCAPED_UNICODE) . "

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
     * Call Google Gemini 1.5 Flash API.
     */
    protected function callGeminiApi(array $messages, array $tools): ?array
    {
        $apiKey = config('ai.gemini.api_key');
        $model = config('ai.gemini.model', 'gemini-1.5-flash');
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

        Log::warning('Gemini API call returned non-200: ' . $response->status() . ' - ' . $response->body());
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

        $title = $selectedPoi['name'] ?? 'Cafe & Trekking Harmony Spot';
        $location = $selectedPoi['name'] ?? ($context['destination'] ?? 'Vietnam');
        $cost = (float) ($selectedPoi['typical_cost'] ?? 150000);
        $poiId = $selectedPoi['id'] ?? null;

        // Custom synthesis based on keywords
        if (str_contains(mb_strtolower($lastUserMsg), 'trekking') && str_contains(mb_strtolower($lastUserMsg), 'cafe')) {
            $title = 'Hòa giải: Cafe View Hẻm Tu Sản & Trekking nhẹ';
            $content = "Dựa trên sở thích của các thành viên, AI đề xuất phương án dung hòa: Ghé quán cafe ngắm cảnh đèo/hẻm núi. Nhóm thích cafe có thể thong thả chụp ảnh, trong khi nhóm thích vận động có thể dạo bộ khám phá cung đường vách đá 30 phút xung quanh.";
        } elseif (str_contains(mb_strtolower($lastUserMsg), 'mưa') || str_contains(mb_strtolower($lastUserMsg), 'weather') || str_contains(mb_strtolower($lastUserMsg), 'thời tiết')) {
            $title = 'Bảo tàng & Trải nghiệm Ẩm thực trong nhà';
            $content = "AI nhận thấy dự báo thời tiết có thể có mưa rào. Đề xuất chuyển các hoạt động ngoài trời sang địa điểm trong nhà có trải nghiệm văn hóa và ẩm thực ấm cúng.";
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
}
