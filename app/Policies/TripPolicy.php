<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;

class TripPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the trip.
     * Allowed if the trip is public, or if the user is the owner or an assigned member.
     */
    public function view(?User $user, Trip $trip): bool
    {
        if ($trip->privacy === 'public') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create trips.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the trip.
     * Allowed only if the user has 'leader' or 'editor' role.
     */
    public function update(User $user, Trip $trip): bool
    {
        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['leader', 'editor'])
            ->exists();
    }

    /**
     * Determine whether the user can delete the trip.
     * Allowed strictly if the user is the owner or holds the 'leader' role.
     */
    public function delete(User $user, Trip $trip): bool
    {
        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()
            ->where('user_id', $user->id)
            ->where('role', 'leader')
            ->exists();
    }

    /**
     * Determine whether the user can view the budget details of the trip.
     * Allowed only for owner, leader, or editor roles. Viewers are forbidden.
     */
    public function viewBudget(User $user, Trip $trip): bool
    {
        if ($trip->owner_id === $user->id) {
            return true;
        }

        return $trip->members()
            ->where('user_id', $user->id)
            ->whereIn('role', ['leader', 'editor'])
            ->exists();
    }

    /**
     * Determine whether the user can manage rooms (create, update, delete, toggle) for the trip.
     * Allowed only for owner, leader, or editor roles.
     */
    public function manageRooms(User $user, Trip $trip): bool
    {
        return $this->update($user, $trip);
    }

    /**
     * Determine whether the user can manage marketplace requests and proposals for the trip.
     * Allowed only for owner, leader, or editor roles.
     */
    public function manageMarketplace(User $user, Trip $trip): bool
    {
        return $this->update($user, $trip);
    }
}
