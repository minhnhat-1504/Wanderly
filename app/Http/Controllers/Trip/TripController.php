<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Trip\StoreTripRequest;
use App\Http\Requests\Trip\UpdateTripRequest;
use App\Models\Destination;
use App\Models\Trip;
use App\Services\TripService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TripController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Display a listing of trips on the user's dashboard.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $trips = $this->tripService->getDashboardTrips($request->user(), $search);

        return view('trips.index', compact('trips'));
    }

    /**
     * Show the form for creating a new trip.
     */
    public function create(): View
    {
        $destinations = collect();
        if (class_exists(Destination::class) && Schema::hasTable('destinations')) {
            $destinations = Destination::all();
        }

        return view('trips.create', compact('destinations'));
    }

    /**
     * Store a newly created trip in storage.
     */
    public function store(StoreTripRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('trips/covers', 'public');
        }

        $trip = $this->tripService->createTrip($data, $request->user());

        return redirect()->route('trips.show', $trip)
            ->with('success', __('Trip created successfully.'));
    }

    /**
     * Display the specified trip details.
     */
    public function show(Trip $trip): View
    {
        $this->authorize('view', $trip);

        $tripDays = $this->tripService->calculateTripDays($trip);
        $trip->load(['members.user', 'owner', 'activities.participants.user', 'activities.payer']);

        return view('trips.show', compact('trip', 'tripDays'));
    }

    /**
     * Show the form for editing the specified trip.
     */
    public function edit(Trip $trip): View
    {
        $this->authorize('update', $trip);

        $destinations = collect();
        if (class_exists(Destination::class) && Schema::hasTable('destinations')) {
            $destinations = Destination::all();
        }

        return view('trips.edit', compact('trip', 'destinations'));
    }

    /**
     * Update the specified trip in storage.
     */
    public function update(UpdateTripRequest $request, Trip $trip): RedirectResponse
    {
        $this->authorize('update', $trip);

        $data = $request->validated();

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('trips/covers', 'public');
        }

        $this->tripService->updateTrip($trip, $data);

        return redirect()->route('trips.show', $trip)
            ->with('success', __('Trip updated successfully.'));
    }

    /**
     * Remove the specified trip from storage.
     */
    public function destroy(Trip $trip): RedirectResponse
    {
        $this->authorize('delete', $trip);

        $this->tripService->deleteTrip($trip);

        return redirect()->route('trips.index')
            ->with('success', __('Trip deleted successfully.'));
    }

    /**
     * Duplicate the specified trip for the authenticated user.
     */
    public function duplicate(Request $request, Trip $trip): RedirectResponse
    {
        $this->authorize('view', $trip);

        $newTrip = $this->tripService->duplicateTrip($trip, $request->user());

        return redirect()->route('trips.show', $newTrip)
            ->with('success', __('Trip duplicated successfully.'));
    }
}
