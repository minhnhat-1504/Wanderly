<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ReorderActivityRequest;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\Trip;
use App\Services\ItineraryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ItineraryService $itineraryService
    ) {}

    /**
     * Store a newly created activity for the trip.
     */
    public function store(StoreActivityRequest $request, Trip $trip): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $trip);

        $validated = $request->validated();
        $activity = $this->itineraryService->createActivity($trip, $validated);

        $hasConflict = $this->itineraryService->detectTimeConflict(
            $trip,
            (int) $activity->day,
            $activity->start_time,
            $activity->end_time,
            $activity->id
        );

        $dailyCost = $this->itineraryService->calculateDailyCost($trip, (int) $activity->day);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Activity created successfully.'),
                'activity' => $activity,
                'daily_cost' => $dailyCost,
                'has_conflict' => $hasConflict,
            ], 201);
        }

        return redirect()->route('trips.show', $trip)
            ->with('success', __('Activity created successfully.'));
    }

    /**
     * Update an existing activity for the trip.
     */
    public function update(UpdateActivityRequest $request, Trip $trip, Activity $activity): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($activity->trip_id !== $trip->id) {
            abort(404);
        }

        $validated = $request->validated();
        $activity = $this->itineraryService->updateActivity($activity, $validated);

        $hasConflict = $this->itineraryService->detectTimeConflict(
            $trip,
            (int) $activity->day,
            $activity->start_time,
            $activity->end_time,
            $activity->id
        );

        $dailyCost = $this->itineraryService->calculateDailyCost($trip, (int) $activity->day);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Activity updated successfully.'),
                'activity' => $activity,
                'daily_cost' => $dailyCost,
                'has_conflict' => $hasConflict,
            ]);
        }

        return redirect()->route('trips.show', $trip)
            ->with('success', __('Activity updated successfully.'));
    }

    /**
     * Remove the specified activity from the trip.
     */
    public function destroy(Request $request, Trip $trip, Activity $activity): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $trip);

        if ($activity->trip_id !== $trip->id) {
            abort(404);
        }

        $day = (int) $activity->day;
        $this->itineraryService->deleteActivity($activity);
        $dailyCost = $this->itineraryService->calculateDailyCost($trip, $day);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('Activity removed successfully.'),
                'daily_cost' => $dailyCost,
            ]);
        }

        return redirect()->route('trips.show', $trip)
            ->with('success', __('Activity removed successfully.'));
    }

    /**
     * Reorder activities across days and time slots via AJAX.
     */
    public function reorder(ReorderActivityRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $this->itineraryService->reorderActivities($trip, $request->validated('items'));

        return response()->json([
            'success' => true,
            'message' => __('Activities reordered successfully.'),
        ]);
    }
}
