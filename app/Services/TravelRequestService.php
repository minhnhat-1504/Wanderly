<?php

namespace App\Services;

use App\Events\TravelRequestCreated;
use App\Models\Destination;
use App\Models\Provider;
use App\Models\TravelRequest;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Collection;

class TravelRequestService
{
    /**
     * Publish a new travel request from a trip and dispatch event.
     *
     * @param  array{
     *     needs: array,
     *     pace?: ?string,
     *     max_budget?: ?float,
     *     notes?: ?string
     * }  $data
     */
    public function publishRequest(Trip $trip, array $data): TravelRequest
    {
        $needs = $data['needs'] ?? [];
        $pace = $data['pace'] ?? null;
        $maxBudget = isset($data['max_budget']) ? (float) $data['max_budget'] : (float) $trip->budget;
        $notes = $data['notes'] ?? null;

        $travelRequest = TravelRequest::create([
            'trip_id' => $trip->id,
            'needs' => $needs,
            'pace' => $pace,
            'max_budget' => $maxBudget,
            'status' => 'open',
            'notes' => $notes,
        ]);

        event(new TravelRequestCreated($travelRequest));

        return $travelRequest;
    }

    /**
     * Match eligible providers for a given travel request based on destination, needs, and schedule availability.
     */
    public function matchProviders(TravelRequest $request): Collection
    {
        $trip = $request->trip;
        if (! $trip) {
            return new Collection;
        }

        $query = Provider::query()->with(['services', 'destination', 'user']);

        // 1. Destination Matching (text-based matching without Map API)
        $destinationIds = Destination::query()
            ->where('name', 'LIKE', '%'.$trip->destination.'%')
            ->pluck('id');

        if ($destinationIds->isNotEmpty()) {
            $query->whereIn('destination_id', $destinationIds);
        }

        // 2. Needs & Service Category Matching
        $needs = (array) ($request->needs ?? []);
        $needKeywords = [];
        foreach ($needs as $key => $val) {
            if (is_string($key) && ! is_numeric($key)) {
                $needKeywords[] = strtolower($key);
            }
            if (is_string($val)) {
                $needKeywords[] = strtolower($val);
            }
        }
        $needKeywords = array_values(array_unique(array_filter($needKeywords)));

        if (! empty($needKeywords)) {
            $query->where(function ($sub) use ($needKeywords) {
                $sub->whereIn('service_type', $needKeywords)
                    ->orWhereHas('services', function ($svcQuery) use ($needKeywords) {
                        $svcQuery->where(function ($keywordQuery) use ($needKeywords) {
                            foreach ($needKeywords as $keyword) {
                                $keywordQuery->orWhere('name', 'LIKE', '%'.$keyword.'%');
                            }
                        });
                    });
            });
        }

        // 3. Blocked Dates Check: provider must NOT have any blocked date during the trip range
        if ($trip->start_date && $trip->end_date) {
            $startDate = $trip->start_date->format('Y-m-d');
            $endDate = $trip->end_date->format('Y-m-d');

            $query->whereDoesntHave('schedules', function ($scheduleQuery) use ($startDate, $endDate) {
                $scheduleQuery->whereBetween('blocked_date', [$startDate, $endDate]);
            });
        }

        return $query->orderByDesc('rating')->get();
    }
}
