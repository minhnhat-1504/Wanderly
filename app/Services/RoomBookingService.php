<?php

namespace App\Services;

use App\Events\RoomBudgetToggled;
use App\Models\Activity;
use App\Models\RoomBooking;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoomBookingService
{
    /**
     * Create a new room booking for a trip.
     */
    public function create(Trip $trip, array $data): RoomBooking
    {
        return DB::transaction(function () use ($trip, $data) {
            $data['trip_id'] = $trip->id;
            if (! isset($data['is_budget_included'])) {
                $data['is_budget_included'] = true;
            }

            $room = RoomBooking::create($data);

            if ($room->is_budget_included && (float) $room->price > 0) {
                RoomBudgetToggled::dispatch($room);
            }

            return $room;
        });
    }

    /**
     * Update an existing room booking.
     */
    public function update(RoomBooking $room, array $data): RoomBooking
    {
        return DB::transaction(function () use ($room, $data) {
            $oldPrice = (float) $room->price;
            $oldIncluded = (bool) $room->is_budget_included;

            $room->update($data);

            $newPrice = (float) $room->price;
            $newIncluded = (bool) $room->is_budget_included;

            if ($oldPrice !== $newPrice || $oldIncluded !== $newIncluded) {
                RoomBudgetToggled::dispatch($room);
            }

            return $room;
        });
    }

    /**
     * Delete a room booking.
     */
    public function delete(RoomBooking $room): bool
    {
        return DB::transaction(function () use ($room) {
            $wasIncluded = (bool) $room->is_budget_included;
            $hadPrice = (float) $room->price > 0;
            $trip = $room->trip;

            $deleted = $room->delete();

            if ($deleted && $wasIncluded && $hadPrice && $trip) {
                $fakeRoom = new RoomBooking(['trip_id' => $trip->id]);
                $fakeRoom->setRelation('trip', $trip);
                RoomBudgetToggled::dispatch($fakeRoom);
            }

            return $deleted;
        });
    }

    /**
     * Toggle whether the room price is included in the trip budget.
     */
    public function toggleBudgetInclusion(RoomBooking $room): bool
    {
        $room->is_budget_included = ! $room->is_budget_included;
        $room->save();

        RoomBudgetToggled::dispatch($room);

        return $room->is_budget_included;
    }

    /**
     * Attach a room booking to a specific day and time slot on the timeline as an Activity with type 'room'.
     */
    public function attachToTimeline(RoomBooking $room, int $day, string $slot): ?object
    {
        if (class_exists(Activity::class) && Schema::hasTable('activities')) {
            /** @var class-string<Model> $activityClass */
            $activityClass = Activity::class;

            return $activityClass::create([
                'trip_id' => $room->trip_id,
                'day' => $day,
                'time_slot' => $slot,
                'title' => $room->hotel_name,
                'cost' => 0.00,
                'category' => 'other',
                'location' => $room->hotel_name,
                'address' => $room->address,
                'notes' => $room->notes,
                'type' => 'room',
                'position' => 0,
            ]);
        }

        return null;
    }
}
