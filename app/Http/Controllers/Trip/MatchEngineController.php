<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\MatchEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MatchEngineController extends Controller
{
    protected MatchEngineService $matchEngineService;

    public function __construct(MatchEngineService $matchEngineService)
    {
        $this->matchEngineService = $matchEngineService;
    }

    /**
     * Generate an intelligent itinerary proposal with full score breakdown.
     */
    public function suggest(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $draft = $this->matchEngineService->generateDraftItinerary($trip);

        return response()->json([
            'success' => true,
            'draft' => $draft,
        ]);
    }

    /**
     * Apply the suggested draft itinerary directly to the trip timeline.
     */
    public function apply(Request $request, Trip $trip): JsonResponse
    {
        Gate::authorize('update', $trip);

        $draft = $request->input('draft');
        if (empty($draft) || ! isset($draft['days'])) {
            // If not provided in request body, re-generate on the fly
            $draft = $this->matchEngineService->generateDraftItinerary($trip);
        }

        $this->matchEngineService->applyDraftToTimeline($trip, $draft);

        return response()->json([
            'success' => true,
            'message' => __('Suggested itinerary has been successfully applied to your timeline!'),
            'redirect' => route('trips.show', $trip),
        ]);
    }
}
