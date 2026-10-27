<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryArticle;
use App\Models\Trip;
use App\Services\DiscoveryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscoveryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DiscoveryService $discoveryService
    ) {}

    /**
     * Display the Vietnam Travel Discovery Portal homepage.
     * Route: GET /discovery
     */
    public function index(Request $request): View
    {
        $regions = $this->discoveryService->getRegions();
        $articles = $this->discoveryService->searchArticles(
            keyword: $request->query('search'),
            region: $request->query('region'),
            category: $request->query('category'),
            perPage: 9
        );
        $itineraries = $this->discoveryService->getCuratedItineraries();

        return view('discovery.index', compact('regions', 'articles', 'itineraries'));
    }

    /**
     * Display a single discovery destination / article.
     * Route: GET /discovery/{slug}
     */
    public function show(string $slug): View
    {
        $article = DiscoveryArticle::query()
            ->published()
            ->with(['region', 'destination'])
            ->where('slug', $slug)
            ->firstOrFail();

        $article->incrementViews();

        $relatedArticles = DiscoveryArticle::query()
            ->published()
            ->where('region_id', $article->region_id)
            ->where('id', '!=', $article->id)
            ->limit(3)
            ->get();

        $userTrips = collect();
        if (auth()->check()) {
            $userTrips = auth()->user()->trips()
                ->latest()
                ->get(['trips.id', 'trips.name', 'trips.destination', 'trips.start_date', 'trips.end_date']);
        }

        return view('discovery.show', compact('article', 'relatedArticles', 'userTrips'));
    }

    /**
     * 1-Click "Add to Current Trip" action.
     * Route: POST /discovery/{article}/add-to-trip
     */
    public function addToTrip(Request $request, DiscoveryArticle $article): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'day' => ['nullable', 'integer', 'min:1', 'max:60'],
            'time_slot' => ['nullable', 'in:morning,midday,afternoon,evening'],
        ]);

        $trip = Trip::findOrFail($validated['trip_id']);

        // Check permission: user must be owner or leader/editor
        $this->authorize('update', $trip);

        $day = (int) ($validated['day'] ?? 1);
        $timeSlot = $validated['time_slot'] ?? 'morning';

        $activity = $this->discoveryService->addToTrip($trip, $article, $day, $timeSlot);

        $message = __(':title has been added to Day :day (:slot) of :trip!', [
            'title' => $article->title,
            'day' => $day,
            'slot' => ucfirst($timeSlot),
            'trip' => $trip->name,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'activity' => $activity,
                'trip_url' => route('trips.show', $trip),
            ]);
        }

        return redirect()->route('trips.show', $trip)->with('success', $message);
    }
}
