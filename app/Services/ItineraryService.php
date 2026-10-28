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

    /**
     * Standard time boundaries for each timeslot.
     *
     * @var array<string, array{start: string, end: string}>
     */
    public const SLOT_BOUNDS = [
        'morning' => ['start' => '06:00', 'end' => '11:00'],
        'midday' => ['start' => '11:00', 'end' => '14:00'],
        'afternoon' => ['start' => '14:00', 'end' => '18:00'],
        'evening' => ['start' => '18:00', 'end' => '23:59'],
    ];

    public function __construct(
        protected TripService $tripService
    ) {}

    /**
     * Group all activities of a trip by day and time slot, ordered by start_time ascending.
     *
     * @return array<int, array<string, Collection<int, Activity>>>
     */
    public function getTripTimeline(Trip $trip): array
    {
        $tripDays = $this->tripService->calculateTripDays($trip);
        $activities = $trip->activities()
            ->orderByRaw('CASE WHEN start_time IS NULL THEN 1 ELSE 0 END, start_time ASC')
            ->orderBy('position', 'asc')
            ->get();

        $timeline = [];

        for ($day = 1; $day <= $tripDays; $day++) {
            $timeline[$day] = [];
            foreach (self::TIME_SLOTS as $slot) {
                $timeline[$day][$slot] = $activities->filter(function (Activity $act) use ($day, $slot) {
                    return (int) $act->day === $day && $act->time_slot === $slot;
                })->sort(function (Activity $a, Activity $b) {
                    if ($a->start_time && $b->start_time) {
                        return strcmp((string) $a->start_time, (string) $b->start_time);
                    }
                    if ($a->start_time) {
                        return -1;
                    }
                    if ($b->start_time) {
                        return 1;
                    }

                    return $a->position <=> $b->position;
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

        $this->resequenceSlotPositions($trip, (int) $activity->day, $activity->time_slot);

        $eventClass = '\App\Events\ActivityCostChanged';
        if (class_exists($eventClass)) {
            event(new $eventClass($trip));
        }

        return $activity->fresh();
    }

    /**
     * Update an existing activity.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        $oldDay = (int) $activity->day;
        $oldSlot = $activity->time_slot;

        $activity->update($data);

        $this->resequenceSlotPositions($activity->trip, (int) $activity->day, $activity->time_slot);
        if ($oldDay !== (int) $activity->day || $oldSlot !== $activity->time_slot) {
            $this->resequenceSlotPositions($activity->trip, $oldDay, $oldSlot);
        }

        $eventClass = '\App\Events\ActivityCostChanged';
        if (class_exists($eventClass)) {
            event(new $eventClass($activity->trip));
        }

        return $activity->fresh();
    }

    /**
     * Automatically resequence activity positions in a slot so earlier start_times come first.
     */
    public function resequenceSlotPositions(Trip $trip, int $day, string $slot): void
    {
        $slotActivities = $trip->activities()
            ->where('day', $day)
            ->where('time_slot', $slot)
            ->orderByRaw('CASE WHEN start_time IS NULL THEN 1 ELSE 0 END, start_time ASC')
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($slotActivities as $index => $act) {
            if ($act->position !== $index) {
                $act->update(['position' => $index]);
            }
        }
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
