<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Services\ChecklistService;
use App\Services\ItineraryService;
use App\Services\ShareService;
use App\Services\TripService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicShareController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ShareService $shareService,
        protected ItineraryService $itineraryService,
        protected BudgetService $budgetService,
        protected ChecklistService $checklistService,
        protected TripService $tripService
    ) {}

    /**
     * Display a shared read-only trip page.
     */
    public function show(string $slug): View
    {
        $trip = $this->shareService->getSharedTrip($slug);

        if (! $trip) {
            abort(404, __('The shared trip you are looking for is either private, revoked, or does not exist.'));
        }

        $tripDays = $this->tripService->calculateTripDays($trip);
        $timelineByDay = $this->itineraryService->getTripTimeline($trip);
        $checklistItems = $this->checklistService->getItems($trip);
        $checklistProgress = $this->checklistService->calculateProgress($trip);

        $budgetStatus = null;
        if ($trip->is_share_budget_visible) {
            $budgetStatus = $this->budgetService->calculateBudgetStatus($trip);
        }

        return view('public.share', compact(
            'trip',
            'tripDays',
            'timelineByDay',
            'checklistItems',
            'checklistProgress',
            'budgetStatus'
        ));
    }

    /**
     * Toggle public sharing on or off for a trip.
     */
    public function toggleShare(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        if ($trip->is_shared) {
            $this->shareService->disableSharing($trip);
        } else {
            $this->shareService->enableSharing($trip, $trip->is_share_budget_visible ?? true);
        }

        $trip->refresh();

        return response()->json([
            'success' => true,
            'is_shared' => $trip->is_shared,
            'share_slug' => $trip->share_slug,
            'share_url' => $trip->share_slug ? url('/s/'.$trip->share_slug) : null,
            'is_share_budget_visible' => $trip->is_share_budget_visible,
            'message' => $trip->is_shared
                ? __('Public sharing enabled.')
                : __('Public sharing disabled.'),
        ]);
    }

    /**
     * Revoke the current share link and generate a fresh slug.
     */
    public function revoke(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $this->shareService->revokeShareLink($trip);
        $trip->refresh();

        return response()->json([
            'success' => true,
            'share_slug' => $trip->share_slug,
            'share_url' => url('/s/'.$trip->share_slug),
            'message' => __('Share link revoked and refreshed successfully.'),
        ]);
    }

    /**
     * Toggle whether budget information is shown on the public share page.
     */
    public function toggleBudget(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $newVisibility = ! $trip->is_share_budget_visible;
        $this->shareService->setBudgetVisibility($trip, $newVisibility);
        $trip->refresh();

        return response()->json([
            'success' => true,
            'is_share_budget_visible' => $trip->is_share_budget_visible,
            'message' => $trip->is_share_budget_visible
                ? __('Budget is now visible on the public page.')
                : __('Budget is now hidden on the public page.'),
        ]);
    }
}
