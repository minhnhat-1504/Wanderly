<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TripService
{
    /**
     * Create a new trip and automatically assign the owner as 'leader'.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws Exception
     */
    public function createTrip(array $data, User $owner): Trip
    {
        DB::beginTransaction();

        try {
            $trip = Trip::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'destination' => $data['destination'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'budget' => $data['budget'] ?? 0,
                'cover' => $data['cover'] ?? null,
                'privacy' => $data['privacy'] ?? 'private',
                'status' => $data['status'] ?? 'planning',
            ]);

            TripMember::create([
                'trip_id' => $trip->id,
                'user_id' => $owner->id,
                'role' => 'leader',
            ]);

            DB::commit();

            return $trip;
        } catch (Exception $e) {
            DB::rollBack();

            // If a cover image was uploaded before failure, clean it up
            if (! empty($data['cover']) && Storage::disk('public')->exists($data['cover'])) {
                Storage::disk('public')->delete($data['cover']);
            }

            throw $e;
        }
    }

    /**
     * Calculate total trip duration in days (inclusive of start and end dates).
     */
    public function calculateTripDays(Trip $trip): int
    {
        $startDate = Carbon::parse($trip->start_date)->startOfDay();
        $endDate = Carbon::parse($trip->end_date)->startOfDay();

        return (int) $startDate->diffInDays($endDate) + 1;
    }

    /**
     * Retrieve all trips associated with the given user (as owner or member)
     * with optional search by name or destination.
     *
     * @return Collection<int, Trip>
     */
    public function getDashboardTrips(User $user, ?string $search = null): Collection
    {
        $query = Trip::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id);
                    });
            })
            ->with(['members.user', 'owner']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }

    /**
     * Update an existing trip.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTrip(Trip $trip, array $data): Trip
    {
        // If a new cover image is uploaded, remove the old one
        if (isset($data['cover']) && $trip->cover && $trip->cover !== $data['cover']) {
            if (Storage::disk('public')->exists($trip->cover)) {
                Storage::disk('public')->delete($trip->cover);
            }
        }

        $trip->update($data);

        return $trip;
    }

    /**
     * Delete a trip and its associated assets.
     */
    public function deleteTrip(Trip $trip): bool
    {
        if ($trip->cover && Storage::disk('public')->exists($trip->cover)) {
            Storage::disk('public')->delete($trip->cover);
        }

        return (bool) $trip->delete();
    }
}
