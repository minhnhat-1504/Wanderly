<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\Proposal;
use App\Models\Trip;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Trip $trip;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Booking $booking,
        public ?Proposal $proposal = null
    ) {
        $this->trip = $booking->trip ?? Trip::findOrFail($booking->trip_id);
    }
}
