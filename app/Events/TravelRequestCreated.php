<?php

namespace App\Events;

use App\Models\TravelRequest;
use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TravelRequestCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public TravelRequest $travelRequest
    ) {
        $this->trip = $travelRequest->trip ?? Trip::findOrFail($travelRequest->trip_id);
    }
}
