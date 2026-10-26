<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Poi;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDestinationController extends Controller
{
    // ─────────────────────────────────────────
    // DESTINATIONS
    // ─────────────────────────────────────────

    public function index(): View
    {
        $destinations = Destination::withCount(['zones', 'pois'])->latest()->get();

        return view('admin.destinations.index', compact('destinations'));
    }

    public function createDestination(): View
    {
        return view('admin.destinations.create');
    }

    public function storeDestination(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:destinations,name'],
            'country' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        Destination::create($validated);

        return redirect()->route('admin.destinations.index')
            ->with('success', __('Destination created successfully.'));
    }

    public function editDestination(Destination $destination): View
    {
        return view('admin.destinations.edit', compact('destination'));
    }

    public function updateDestination(Request $request, Destination $destination): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:destinations,name,'.$destination->id],
            'country' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $destination->update($validated);

        return redirect()->route('admin.destinations.index')
            ->with('success', __('Destination updated successfully.'));
    }

    public function destroyDestination(Destination $destination): RedirectResponse
    {
        $destination->delete();

        return redirect()->route('admin.destinations.index')
            ->with('success', __('Destination deleted.'));
    }

    // ─────────────────────────────────────────
    // ZONES
    // ─────────────────────────────────────────

    public function zones(Destination $destination): View
    {
        $zones = $destination->zones()->withCount('pois')->latest()->get();

        return view('admin.zones.index', compact('destination', 'zones'));
    }

    public function storeZone(Request $request, Destination $destination): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $destination->zones()->create($validated);

        return redirect()->route('admin.destinations.zones', $destination)
            ->with('success', __('Zone created successfully.'));
    }

    public function updateZone(Request $request, Zone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $zone->update($validated);

        return back()->with('success', __('Zone updated.'));
    }

    public function destroyZone(Zone $zone): RedirectResponse
    {
        $destination = $zone->destination;
        $zone->delete();

        return redirect()->route('admin.destinations.zones', $destination)
            ->with('success', __('Zone deleted.'));
    }

    // ─────────────────────────────────────────
    // POIs
    // ─────────────────────────────────────────

    public function pois(Zone $zone): View
    {
        $pois = $zone->pois()->latest()->get();

        return view('admin.pois.index', compact('zone', 'pois'));
    }

    public function storePoi(Request $request, Zone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'description' => ['nullable', 'string'],
            'avg_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $zone->pois()->create($validated);

        return redirect()->route('admin.zones.pois', $zone)
            ->with('success', __('POI created successfully.'));
    }

    public function updatePoi(Request $request, Poi $poi): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'description' => ['nullable', 'string'],
            'avg_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $poi->update($validated);

        return back()->with('success', __('POI updated.'));
    }

    public function destroyPoi(Poi $poi): RedirectResponse
    {
        $zone = $poi->zone;
        $poi->delete();

        return redirect()->route('admin.zones.pois', $zone)
            ->with('success', __('POI deleted.'));
    }
}
