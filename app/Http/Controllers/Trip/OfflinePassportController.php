<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\OfflinePassportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class OfflinePassportController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected OfflinePassportService $passportService
    ) {}

    /**
     * Display the Offline Travel Passport page.
     * Route: GET /trips/{trip}/offline-passport
     */
    public function show(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $snapshot = $this->passportService->generateOfflineSnapshot($trip);

        return view('trips.offline-passport', [
            'trip' => $trip,
            'snapshot' => $snapshot,
        ]);
    }

    /**
     * Return the snapshot as JSON for client-side offline caching.
     * Route: GET /trips/{trip}/offline-passport/data
     */
    public function data(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        $snapshot = $this->passportService->generateOfflineSnapshot($trip);

        return response()->json([
            'success' => true,
            'data' => $snapshot,
        ]);
    }
}
