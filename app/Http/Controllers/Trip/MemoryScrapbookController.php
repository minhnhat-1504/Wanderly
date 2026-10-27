<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\ReverseMemoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MemoryScrapbookController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ReverseMemoryService $memoryService
    ) {}

    /**
     * Display the digital photo scrapbook memory album for the trip.
     * Route: GET /trips/{trip}/memory-scrapbook
     */
    public function show(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $scrapbook = $this->memoryService->generateScrapbook($trip);

        return view('trips.memory-scrapbook', [
            'trip' => $trip,
            'scrapbook' => $scrapbook,
        ]);
    }

    /**
     * Return scrapbook data as JSON.
     * Route: GET /trips/{trip}/memory-scrapbook/data
     */
    public function data(Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        $scrapbook = $this->memoryService->generateScrapbook($trip);

        return response()->json([
            'success' => true,
            'data' => $scrapbook,
        ]);
    }
}
