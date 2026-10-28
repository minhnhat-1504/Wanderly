<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AiChatMessage;
use App\Models\Trip;
use App\Models\TripMember;
use App\Services\ActionEngineService;
use App\Services\AiCopilotService;
use App\Services\TripService;
use App\Services\WeatherAdaptiveService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AiCopilotController extends Controller
{
    public function __construct(
        protected AiCopilotService $aiCopilotService,
        protected ActionEngineService $actionEngineService,
        protected WeatherAdaptiveService $weatherService,
        protected TripService $tripService
    ) {}

    /**
     * Get global chat messages for user (not associated with any trip).
     */
    public function getGlobalMessages(Request $request): JsonResponse
    {
        $messages = AiChatMessage::whereNull('trip_id')
            ->where('user_id', $request->user()->id)
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Clear global chat messages history for user.
     */
    public function clearGlobalMessages(Request $request): JsonResponse
    {
        AiChatMessage::whereNull('trip_id')
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => __('Chat history cleared successfully.'),
        ]);
    }

    /**
     * Send global message to AI Travel Assistant.
     */
    public function sendGlobalMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1500',
        ]);

        $user = $request->user();

        // 1. Save user message
        $userMsg = AiChatMessage::create([
            'trip_id' => null,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // 2. Fetch last 6 messages as conversation history
        $recentHistory = AiChatMessage::whereNull('trip_id')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        // 3. Call AI
        $aiResult = $this->aiCopilotService->chatGeneralTravel($validated['message'], $recentHistory);

        // 4. Save assistant message
        $assistantMsg = AiChatMessage::create([
            'trip_id' => null,
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'suggested_action' => $aiResult['suggested_action'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'user_message' => $userMsg,
            'assistant_message' => $assistantMsg,
        ]);
    }

    /**
     * Get chat messages history for a trip.
     */
    public function getMessages(Trip $trip): JsonResponse
    {
        $messages = $trip->aiChatMessages()
            ->with('user:id,name,avatar')
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Clear chat messages history for a trip.
     */
    public function clearTripMessages(Request $request, Trip $trip): JsonResponse
    {
        $userId = $request->user()->id;
        $isMember = $trip->members()->where('user_id', $userId)->exists() || $trip->owner_id === $userId;
        if (! $isMember) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $trip->aiChatMessages()->delete();

        return response()->json([
            'success' => true,
            'message' => __('Trip chat history cleared successfully.'),
        ]);
    }

    /**
     * Send message to AI Co-pilot and receive guidance / action cards.
     */
    public function sendMessage(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1500',
        ]);

        $user = $request->user();

        // 1. Save user message
        $userMsg = $trip->aiChatMessages()->create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // 2. Call AI Copilot
        $aiResult = $this->aiCopilotService->mediateGroupConflict($trip, $validated['message']);

        // 3. Save assistant message
        $assistantMsg = $trip->aiChatMessages()->create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $aiResult['content'],
            'suggested_action' => $aiResult['suggested_action'],
        ]);

        return response()->json([
            'success' => true,
            'user_message' => $userMsg,
            'assistant_message' => $assistantMsg,
        ]);
    }

    /**
     * Execute an AI suggested action and apply it to timeline.
     */
    public function applyAction(Request $request, Trip $trip, AiChatMessage $message): JsonResponse
    {
        // Must be trip owner, leader, or editor
        $isOwner = (int) $trip->owner_id === (int) $request->user()->id;
        $isLeaderOrEditor = $trip->members()
            ->where('user_id', $request->user()->id)
            ->whereIn('role', ['leader', 'editor'])
            ->exists();

        if (! $isOwner && ! $isLeaderOrEditor) {
            return response()->json([
                'success' => false,
                'message' => __('You do not have permission to modify the timeline.'),
            ], 403);
        }

        if ($message->trip_id !== $trip->id || empty($message->suggested_action)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid action or not found for this trip.'),
            ], 404);
        }

        if ($message->is_applied) {
            return response()->json([
                'success' => false,
                'message' => __('This action has already been applied.'),
            ], 422);
        }

        $actionData = $message->suggested_action;
        $type = $actionData['type'] ?? 'create_activity';
        $params = $actionData['params'] ?? [];

        $activity = $this->actionEngineService->executeAction($type, $params, $trip, $request->user());

        $message->update(['is_applied' => true]);

        return response()->json([
            'success' => true,
            'message' => __('Action successfully applied to itinerary timeline!'),
            'activity' => $activity,
        ]);
    }

    /**
     * Parse raw review text into draft activities.
     */
    public function ingestReview(Request $request, Trip $trip): JsonResponse
    {
        $validated = $request->validate([
            'review_text' => 'required|string|max:5000',
        ]);

        $activities = $this->actionEngineService->ingestReviewText($validated['review_text'], $trip);

        return response()->json([
            'success' => true,
            'activities' => $activities,
        ]);
    }

    /**
     * Get weather forecast and outdoor conflicts.
     */
    public function getWeatherAlerts(Trip $trip): JsonResponse
    {
        $forecast = $this->weatherService->getForecast($trip);
        $conflicts = $this->weatherService->detectWeatherConflicts($trip);

        return response()->json([
            'success' => true,
            'forecast' => $forecast,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Create a concrete Trip with activities from a verified itinerary plan.
     */
    public function createTripFromItinerary(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'province' => 'required|array',
            'days' => 'required|array|min:1',
            'start_date' => 'nullable|date',
        ]);

        $province = $validated['province'];
        $days = $validated['days'];
        $dayCount = count($days);

        $startDate = ! empty($validated['start_date']) ? Carbon::parse($validated['start_date']) : Carbon::tomorrow();
        $endDate = $startDate->copy()->addDays($dayCount - 1);

        // 1. Create Trip using TripService (automatically sets leader role)
        $trip = $this->tripService->createTrip([
            'name' => 'Chuyến đi ' . ($province['name'] ?? 'Việt Nam') . " ({$dayCount} ngày)",
            'destination' => $province['name'] ?? 'Việt Nam',
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'budget' => 2000000 * $dayCount,
            'privacy' => 'private',
            'status' => 'planning',
        ], $user);

        // 2. Create activities for each day with strict enum mappings
        foreach ($days as $dayData) {
            $dayNum = (int) ($dayData['day'] ?? 1);
            $items = $dayData['items'] ?? [];

            foreach ($items as $pos => $item) {
                $place = $item['place'] ?? [];
                $duration = (int) ($item['duration_min'] ?? 60);
                $startTimeStr = $item['time'] ?? '08:00';

                // Calculate end time
                $parts = explode(':', $startTimeStr);
                $startH = (int) ($parts[0] ?? 8);
                $startM = (int) ($parts[1] ?? 0);
                $endTotalMinutes = ($startH * 60) + $startM + $duration;
                $endH = floor($endTotalMinutes / 60) % 24;
                $endM = $endTotalMinutes % 60;
                $endTimeStr = sprintf('%02d:%02d', $endH, $endM);
                $formattedStartTime = sprintf('%02d:%02d', $startH, $startM);

                // Map time slot enum: ['morning', 'midday', 'afternoon', 'evening']
                $timeSlot = 'morning';
                if ($startH >= 11 && $startH < 14) {
                    $timeSlot = 'midday';
                } elseif ($startH >= 14 && $startH < 18) {
                    $timeSlot = 'afternoon';
                } elseif ($startH >= 18) {
                    $timeSlot = 'evening';
                }

                // Map category enum: ['sightseeing', 'dining', 'cafe', 'shopping', 'entertainment', 'other']
                $rawCategory = $place['category'] ?? '';
                $category = match ($rawCategory) {
                    'food' => 'dining',
                    'cafe' => 'cafe',
                    'market' => 'shopping',
                    'entertainment', 'experience' => 'entertainment',
                    default => 'sightseeing',
                };

                $ticketPrice = 0;
                $rawPrice = $place['ticket_price_vnd'] ?? null;
                if (is_numeric($rawPrice)) {
                    $ticketPrice = (float) $rawPrice;
                }

                $hoursNote = '';
                if (! empty($place['opening_hours']) && is_array($place['opening_hours'])) {
                    $hoursNote = " (Mở cửa: {$place['opening_hours']['open']}-{$place['opening_hours']['close']})";
                }

                Activity::create([
                    'trip_id' => $trip->id,
                    'day' => $dayNum,
                    'time_slot' => $timeSlot,
                    'start_time' => $formattedStartTime,
                    'end_time' => $endTimeStr,
                    'title' => $place['name_vi'] ?? $item['activity'] ?? 'Điểm tham quan',
                    'cost' => $ticketPrice,
                    'category' => $category,
                    'location' => $place['name_vi'] ?? ($province['name'] ?? ''),
                    'address' => $place['address'] ?? ($place['name_vi'] ?? ''),
                    'notes' => trim(($item['note'] ?? '') . $hoursNote),
                    'position' => $pos,
                    'payer_id' => $user->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo chuyến đi thành công!',
            'trip_id' => $trip->id,
            'redirect_url' => route('trips.show', $trip),
        ]);
    }
}
