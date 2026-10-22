<?php

namespace App\Events;

use App\Models\RoomBooking;
use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomBudgetToggled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public RoomBooking $roomBooking
    ) {
        $this->trip = $roomBooking->trip ?? Trip::findOrFail($roomBooking->trip_id);
    }
}
