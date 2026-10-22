<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Trip;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItineraryService
{
    /**
     * Standard time slots available per day.
     */
    public const TIME_SLOTS = ['morning', 'midday', 'afternoon', 'evening'];

    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Group all activities of a trip by day and time slot, ordered by position.
     *
     * @return array<int, array<string, Collection<int, Activity>>>
     */
    public function getTripTimeline(Trip $trip): array
    {
        $tripDays = $this->tripService->calculateTripDays($trip);
        $activities = $trip->activities()
            ->orderBy('position', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $timeline = [];

        for ($day = 1; $day <= $tripDays; $day++) {
            $timeline[$day] = [];
            foreach (self::TIME_SLOTS as $slot) {
                $timeline[$day][$slot] = $activities->filter(function (Activity $act) use ($day, $slot) {
                    return (int) $act->day === $day && $act->time_slot === $slot;
                })->values();
            }
        }

        return $timeline;
    }

    /**
     * Calculate total cost of activities for a specific day in the trip.
     */
    public function calculateDailyCost(Trip $trip, int $day): float
    {
        return (float) $trip->activities()
            ->where('day', $day)
            ->sum('cost');
    }

    /**
     * Atomic reorder and moving of activities across days, slots, and positions.
     *
     * @param  array<int, array{id: int, day: int, time_slot: string, position: int}>  $reorderItems
     *
     * @throws Exception
     */
    public function reorderActivities(Trip $trip, array $reorderItems): bool
    {
        DB::beginTransaction();

        try {
            foreach ($reorderItems as $item) {
                $trip->activities()
                    ->where('id', $item['id'])
                    ->update([
                        'day' => $item['day'],
                        'time_slot' => $item['time_slot'],
                        'position' => $item['position'],
                    ]);
            }

            // Fire event if ActivityCostChanged event class is registered (Sprint 2 / Member 2)
            $eventClass = '\App\Events\ActivityCostChanged';
            if (class_exists($eventClass)) {
                event(new $eventClass($trip));
            }

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Detect if a given time range conflicts with existing activities on the same day.
     */
    public function detectTimeConflict(
        Trip $trip,
        int $day,
        ?string $startTime,
        ?string $endTime,
        ?int $excludeActivityId = null
    ): bool {
        if (empty($startTime) || empty($endTime)) {
            return false;
        }

        $query = $trip->activities()
            ->where('day', $day)
            ->whereNotNull('start_time')
            ->whereNotNull('end_time');

        if ($excludeActivityId) {
            $query->where('id', '!=', $excludeActivityId);
        }

        $activities = $query->get();

        $newStart = Carbon::createFromFormat('H:i', substr($startTime, 0, 5));
        $newEnd = Carbon::createFromFormat('H:i', substr($endTime, 0, 5));

        foreach ($activities as $activity) {
            $actStart = Carbon::createFromFormat('H:i', substr((string) $activity->start_time, 0, 5));
            $actEnd = Carbon::createFromFormat('H:i', substr((string) $activity->end_time, 0, 5));

            // Overlap condition: actStart < newEnd && actEnd > newStart
            if ($actStart->lt($newEnd) && $actEnd->gt($newStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a new activity for a trip.
     *
     * @param  array<string, mixed>  $data
     */
    public function createActivity(Trip $trip, array $data): Activity
    {
        // If position is not specified, place at end of slot
        if (! isset($data['position'])) {
            $maxPos = (int) $trip->activities()
                ->where('day', $data['day'])
                ->where('time_slot', $data['time_slot'])
                ->max('position');

            $data['position'] = $maxPos + 1;
        }

        $data['trip_id'] = $trip->id;
        $activity = Activity::create($data);

        $eventClass = '\App\Events\ActivityCostChanged';
        if (class_exists($eventClass)) {
            event(new $eventClass($trip));
        }

        return $activity;
    }

    /**
     * Update an existing activity.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        $activity->update($data);

        $eventClass = '\App\Events\ActivityCostChanged';
        if (class_exists($eventClass)) {
            event(new $eventClass($activity->trip));
        }

        return $activity;
    }

    /**
     * Delete an activity and refresh budget if needed.
     */
    public function deleteActivity(Activity $activity): bool
    {
        $trip = $activity->trip;
        $deleted = (bool) $activity->delete();

        $eventClass = '\App\Events\ActivityCostChanged';
        if ($deleted && class_exists($eventClass) && $trip) {
            event(new $eventClass($trip));
        }

        return $deleted;
    }
}
