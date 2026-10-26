<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Only the owning provider may update or delete a catalog service.
     */
    public function manage(User $user, Service $service): bool
    {
        return $service->provider?->user_id === $user->id;
    }
}
