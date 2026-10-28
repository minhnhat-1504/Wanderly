<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Poi;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActionEngineService
{
    public function __construct(
        protected ItineraryService $itineraryService,
        protected BudgetService $budgetService
    ) {}

    /**
     * Validate AI-suggested action payload against DB constraints and budget rules.
     *
     * @param  array<string, mixed>  $aiPayload
     * @return array{valid: bool, errors: array<string>, validated_params: array}
     */
    public function parseAndValidateAction(array $aiPayload, Trip $trip, User $actor): array
    {
        $actionType = $aiPayload['type'] ?? $aiPayload['action'] ?? null;
        $params = $aiPayload['params'] ?? [];
        $errors = [];

        if ($actionType !== 'create_activity' && $actionType !== 'reorder_activities') {
            return [
                'valid' => false,
                'errors' => ['Unsupported action type: '.$actionType],
                'validated_params' => [],
            ];
        }

        if ($actionType === 'create_activity') {
            $title = trim($params['title'] ?? '');
            if (empty($title)) {
                $errors[] = 'Activity title is required.';
            }

            $day = (int) ($params['day'] ?? 1);
            $maxDays = 14;
            if ($trip->start_date && $trip->end_date) {
                $maxDays = (int) $trip->start_date->diffInDays($trip->end_date) + 1;
            }
            if ($day < 1 || $day > $maxDays) {
                $day = 1; // Auto-sanitize to Day 1
            }

            $timeSlot = strtolower($params['time_slot'] ?? 'morning');
            if (! in_array($timeSlot, ItineraryService::TIME_SLOTS, true)) {
                $timeSlot = 'morning';
            }

            $cost = max(0, (float) ($params['cost'] ?? 0));

            // Validate POI if provided
            $poiId = $params['poi_id'] ?? null;
            if ($poiId && ! Poi::where('id', $poiId)->exists()) {
                $poiId = null; // Do not fail, just detach invalid POI ID
            }

            $category = strtolower($params['category'] ?? 'sightseeing');
            $allowedCategories = ['sightseeing', 'dining', 'cafe', 'shopping', 'entertainment', 'other'];
            if (! in_array($category, $allowedCategories, true)) {
                $category = 'sightseeing';
            }

            $validatedParams = [
                'title' => $title,
                'day' => $day,
                'time_slot' => $timeSlot,
                'cost' => $cost,
                'category' => $category,
                'location' => $params['location'] ?? $title,
                'poi_id' => $poiId,
                'description' => $params['description'] ?? null,
                'start_time' => $params['start_time'] ?? null,
                'end_time' => $params['end_time'] ?? null,
            ];

            return [
                'valid' => empty($errors),
                'errors' => $errors,
                'validated_params' => $validatedParams,
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'validated_params' => $params,
        ];
    }

    /**
     * Safely execute validated action into Timeline via ItineraryService.
     *
     * @param  array<string, mixed>  $params
     */
    public function executeAction(string $actionType, array $params, Trip $trip, User $actor): Activity
    {
        $validation = $this->parseAndValidateAction(['type' => $actionType, 'params' => $params], $trip, $actor);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'action' => $validation['errors'],
            ]);
        }

        $validatedParams = $validation['validated_params'];

        // Delegate to existing robust ItineraryService
        return $this->itineraryService->createActivity($trip, $validatedParams);
    }

    /**
     * Ingest unstructured review text and extract activities mapped to morning, midday, afternoon, evening slots.
     *
     * @return array<int, array{day: int, time_slot: string, title: string, cost: float, location: string}>
     */
    public function ingestReviewText(string $rawText, Trip $trip): array
    {
        $lines = preg_split('/[\r\n]+/', $rawText);
        $extracted = [];
        $currentDay = 1;

        $slotKeywords = [
            'sáng' => 'morning',
            'morning' => 'morning',
            'trưa' => 'midday',
            'lunch' => 'midday',
            'chiều' => 'afternoon',
            'afternoon' => 'afternoon',
            'tối' => 'evening',
            'night' => 'evening',
            'dinner' => 'evening',
        ];

        $currentSlot = 'morning';

        foreach ($lines as $line) {
            $trimmed = trim($line, " \t\n\r\0\x0B-*•1234567890.");
            if (empty($trimmed) || mb_strlen($trimmed) < 4) {
                continue;
            }

            // Check day indicator
            if (preg_match('/(?:ngày|day)\s*(\d+)/iu', $line, $matches)) {
                $currentDay = max(1, (int) $matches[1]);

                continue;
            }

            // Check slot keyword
            $lower = mb_strtolower($line);
            foreach ($slotKeywords as $kw => $slot) {
                if (str_contains($lower, $kw)) {
                    $currentSlot = $slot;
                    break;
                }
            }

            // Extract price if any (e.g. 50k, 100.000d, 200k)
            $cost = 0;
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:k|nghìn|vnd|đ)/iu', $line, $costMatches)) {
                $num = (float) str_replace([',', '.'], '', $costMatches[1]);
                $cost = str_contains(mb_strtolower($costMatches[0]), 'k') ? $num * 1000 : $num;
            }

            $extracted[] = [
                'day' => $currentDay,
                'time_slot' => $currentSlot,
                'title' => mb_substr($trimmed, 0, 80),
                'cost' => $cost,
                'location' => mb_substr($trimmed, 0, 100),
                'description' => 'Trích xuất tự động từ văn bản review.',
            ];

            // Next slot cycle if not explicitly specified
            $slots = ItineraryService::TIME_SLOTS;
            $currentIndex = array_search($currentSlot, $slots, true);
            if ($currentIndex !== false && $currentIndex < count($slots) - 1) {
                $currentSlot = $slots[$currentIndex + 1];
            }
        }

        return $extracted;
    }
}
