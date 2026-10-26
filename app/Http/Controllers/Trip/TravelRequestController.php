<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreTravelRequestRequest;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Services\TravelRequestService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TravelRequestController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TravelRequestService $travelRequestService
    ) {}

    /**
     * Display a listing of travel requests for the trip.
     */
    public function index(Request $request, Trip $trip): View|JsonResponse
    {
        $this->authorize('view', $trip);

        $requests = $trip->travelRequests()
            ->withCount('proposals')
            ->latest()
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'requests' => $requests,
            ]);
        }

        return view('marketplace.index', compact('trip', 'requests'));
    }

    /**
     * Show the form for creating a new travel request.
     */
    public function create(Trip $trip): View
    {
        $this->authorize('manageMarketplace', $trip);

        return view('marketplace.request-create', compact('trip'));
    }

    /**
     * Store a newly created travel request in storage.
     */
    public function store(StoreTravelRequestRequest $request, Trip $trip): RedirectResponse|JsonResponse
    {
        $this->authorize('manageMarketplace', $trip);

        $travelRequest = $this->travelRequestService->publishRequest($trip, $request->validated());
        $matchedProviders = $this->travelRequestService->matchProviders($travelRequest);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Travel request published successfully! Matched with :count providers.', ['count' => $matchedProviders->count()]),
                'data' => $travelRequest,
                'matched_count' => $matchedProviders->count(),
            ], 201);
        }

        return redirect()->route('trips.marketplace.compare', [$trip, $travelRequest])
            ->with('success', __('Travel request published! We found :count matching providers in :destination.', [
                'count' => $matchedProviders->count(),
                'destination' => $trip->destination,
            ]));
    }

    /**
     * Cancel an existing travel request.
     */
    public function destroy(Request $request, Trip $trip, TravelRequest $travelRequest): RedirectResponse|JsonResponse
    {
        $this->authorize('manageMarketplace', $trip);

        if ($travelRequest->trip_id !== $trip->id) {
            abort(404);
        }

        $travelRequest->update(['status' => 'cancelled']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Travel request has been cancelled.'),
            ]);
        }

        return redirect()->route('trips.marketplace.index', $trip)
            ->with('success', __('Travel request has been cancelled.'));
    }
}
