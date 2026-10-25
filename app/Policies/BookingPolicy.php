<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the booking.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($booking->trip && $booking->trip->owner_id === $user->id) {
            return true;
        }

        if ($booking->provider && $booking->provider->user_id === $user->id) {
            return true;
        }

        return $booking->trip && $booking->trip->members()->where('user_id', $user->id)->exists();
    }
}
