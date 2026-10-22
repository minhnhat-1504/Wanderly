<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Str;

class ShareService
{
    /**
     * Enable public sharing for a trip and generate a secure slug if missing.
     */
    public function enableSharing(Trip $trip, bool $budgetVisible = true): Trip
    {
        if (empty($trip->share_slug)) {
            $trip->share_slug = $this->generateUniqueSlug();
        }

        $trip->is_shared = true;
        $trip->is_share_budget_visible = $budgetVisible;
        $trip->save();

        return $trip;
    }

    /**
     * Disable public sharing for a trip without invalidating the existing slug.
     */
    public function disableSharing(Trip $trip): Trip
    {
        $trip->is_shared = false;
        $trip->save();

        return $trip;
    }

    /**
     * Revoke existing sharing link by generating a brand new unique slug.
     */
    public function revokeShareLink(Trip $trip): Trip
    {
        $trip->share_slug = $this->generateUniqueSlug();
        $trip->save();

        return $trip;
    }

    /**
     * Toggle budget visibility on the public share page.
     */
    public function setBudgetVisibility(Trip $trip, bool $visible): Trip
    {
        $trip->is_share_budget_visible = $visible;
        $trip->save();

        return $trip;
    }

    /**
     * Retrieve a public trip by its share slug.
     */
    public function getSharedTrip(string $slug): ?Trip
    {
        return Trip::query()
            ->where('share_slug', $slug)
            ->where('is_shared', true)
            ->with(['activities' => function ($q) {
                $q->orderBy('day')->orderBy('position');
            }, 'owner', 'roomBookings'])
            ->first();
    }

    /**
     * Generate an alphanumeric secure 16-character unique slug.
     */
    protected function generateUniqueSlug(): string
    {
        do {
            $slug = Str::lower(Str::random(16));
        } while (Trip::where('share_slug', $slug)->exists());

        return $slug;
    }
}
