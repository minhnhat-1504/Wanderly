<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Poi;
use App\Models\Service;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class VotingService
{
    /**
     * Get candidate pool of POIs and Services for the trip destination.
     */
    public function getCandidatePool(Trip $trip, ?User $user = null): Collection
    {
        // Resolve destination if matched
        $destination = Destination::where('name', 'like', '%'.$trip->destination.'%')->first();

        if ($destination) {
            $pois = Poi::whereIn('zone_id', $destination->zones()->pluck('id'))
                ->with('zone')
                ->get();
        } else {
            $pois = Poi::with('zone')->take(20)->get();
        }

        // Get all votes for this trip
        $votes = Vote::where('trip_id', $trip->id)->get();

        $candidates = collect();

        foreach ($pois as $poi) {
            $poiVotes = $votes->filter(function ($v) use ($poi) {
                return ($v->votable_type === 'poi' || $v->votable_type === Poi::class)
                    && (int) $v->votable_id === (int) $poi->id;
            });

            $upvotes = $poiVotes->where('value', 1)->count();
            $downvotes = $poiVotes->where('value', -1)->count();
            $score = $upvotes - $downvotes;

            $userVote = 0;
            if ($user) {
                $myVote = $poiVotes->firstWhere('user_id', $user->id);
                $userVote = $myVote ? (int) $myVote->value : 0;
            }

            $cost = (float) ($poi->typical_cost ?? $poi->avg_cost ?? 0);
            $rating = (float) ($poi->rating ?? 4.5);

            $candidates->push((object) [
                'id' => $poi->id,
                'key' => 'poi_'.$poi->id,
                'type' => 'poi',
                'votable_type' => 'poi',
                'votable_id' => $poi->id,
                'name' => $poi->name,
                'category' => $poi->category ?? 'sightseeing',
                'zone' => $poi->zone?->name ?? 'City Center',
                'zone_id' => $poi->zone_id,
                'cost' => $cost,
                'rating' => $rating,
                'description' => $poi->description ?? $poi->address ?? '',
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'score' => $score,
                'user_vote' => $userVote,
            ]);
        }

        // Also include active provider services if destination matches
        if ($destination) {
            $services = Service::whereHas('provider', function ($q) use ($destination) {
                $q->where('destination_id', $destination->id);
            })->with('provider')->get();

            foreach ($services as $srv) {
                $srvVotes = $votes->filter(function ($v) use ($srv) {
                    return ($v->votable_type === 'service' || $v->votable_type === Service::class)
                        && (int) $v->votable_id === (int) $srv->id;
                });

                $upvotes = $srvVotes->where('value', 1)->count();
                $downvotes = $srvVotes->where('value', -1)->count();
                $score = $upvotes - $downvotes;

                $userVote = 0;
                if ($user) {
                    $myVote = $srvVotes->firstWhere('user_id', $user->id);
                    $userVote = $myVote ? (int) $myVote->value : 0;
                }

                $candidates->push((object) [
                    'id' => $srv->id,
                    'key' => 'service_'.$srv->id,
                    'type' => 'service',
                    'votable_type' => 'service',
                    'votable_id' => $srv->id,
                    'name' => $srv->name,
                    'category' => 'activity',
                    'zone' => $srv->provider?->business_name ?? 'Provider Service',
                    'zone_id' => null,
                    'cost' => (float) $srv->price,
                    'rating' => (float) ($srv->provider?->rating ?? 4.8),
                    'description' => $srv->provider?->business_name ?? '',
                    'upvotes' => $upvotes,
                    'downvotes' => $downvotes,
                    'score' => $score,
                    'user_vote' => $userVote,
                ]);
            }
        }

        return $candidates;
    }

    /**
     * Cast or update user vote for a candidate.
     */
    public function castVote(Trip $trip, User $user, string $votableType, int $votableId, int $value): Vote
    {
        if (! in_array($value, [1, -1])) {
            throw new InvalidArgumentException(__('Vote value must be either +1 or -1.'));
        }

        $typeKey = strtolower($votableType);
        if (str_contains($typeKey, 'poi')) {
            $normalizedType = 'poi';
        } elseif (str_contains($typeKey, 'service')) {
            $normalizedType = 'service';
        } else {
            $normalizedType = $votableType;
        }

        $existing = Vote::where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($normalizedType, $votableType) {
                $q->where('votable_type', $normalizedType)
                    ->orWhere('votable_type', $votableType);
            })
            ->where('votable_id', $votableId)
            ->first();

        if ($existing) {
            if ((int) $existing->value === $value) {
                // Toggle vote off if clicked the same value
                $existing->delete();
                $existing->value = 0;

                return $existing;
            }

            $existing->update(['value' => $value]);

            return $existing;
        }

        return Vote::create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'votable_type' => $normalizedType,
            'votable_id' => $votableId,
            'value' => $value,
        ]);
    }

    /**
     * Get candidates sorted by net vote score (leaderboard).
     */
    public function getVotingLeaderboard(Trip $trip, ?User $user = null): Collection
    {
        return $this->getCandidatePool($trip, $user)
            ->sortByDesc('rating')
            ->sortByDesc('score')
            ->values();
    }
}
