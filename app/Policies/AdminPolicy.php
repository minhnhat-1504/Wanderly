<?php

namespace App\Policies;

use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can access the admin portal.
     */
    public function viewAdmin(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage users.
     */
    public function manageUsers(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage community moderation.
     */
    public function manageModeration(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage backups.
     */
    public function manageBackups(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage system broadcasts.
     */
    public function manageBroadcasts(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can view audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->role === 'admin';
    }
}
