<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    /**
     * Only the owning user may manage a provider profile, its catalog and calendar.
     */
    public function manage(User $user, Provider $provider): bool
    {
        return $provider->user_id === $user->id;
    }
}
