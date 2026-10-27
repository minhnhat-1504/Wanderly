<?php

namespace App\Policies;

use App\Models\User;

class ProfilePolicy
{
    /**
     * Determine whether the user can view the profile.
     */
    public function view(?User $viewer, User $profileOwner): bool
    {
        // Owner and Admin can always view
        if ($viewer && ($viewer->id === $profileOwner->id || $viewer->role === 'admin')) {
            return true;
        }

        // Public profiles can be viewed by anyone
        return (bool) $profileOwner->is_profile_public;
    }

    /**
     * Determine whether the user can update the profile.
     */
    public function update(User $user, User $profileOwner): bool
    {
        return $user->id === $profileOwner->id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the account.
     */
    public function delete(User $user, User $profileOwner): bool
    {
        return $user->id === $profileOwner->id;
    }
}
