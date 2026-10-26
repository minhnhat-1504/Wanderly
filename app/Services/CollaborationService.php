<?php

namespace App\Services;

use App\Models\MemberPreference;
use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CollaborationService
{
    /**
     * Generate a secure random invite link with 7-day expiration.
     */
    public function generateInviteLink(Trip $trip, string $role = 'editor'): string
    {
        $token = Str::random(32);
        $expiresAt = now()->addDays(7);

        TripMember::create([
            'trip_id' => $trip->id,
            'user_id' => null,
            'role' => in_array($role, ['leader', 'editor', 'viewer']) ? $role : 'editor',
            'invite_token' => $token,
            'token_expires_at' => $expiresAt,
        ]);

        return url('/trips/join/'.$token);
    }

    /**
     * Accept trip invitation using the token and join as member.
     */
    public function acceptInvite(string $token, User $user, ?array $preferences = null): Trip
    {
        $invitation = TripMember::where('invite_token', $token)
            ->where(function ($query) {
                $query->whereNull('token_expires_at')
                    ->orWhere('token_expires_at', '>=', now());
            })
            ->first();

        if (! $invitation) {
            throw new InvalidArgumentException(__('The invite link is invalid or has expired.'));
        }

        $trip = $invitation->trip;

        // Prevent duplicate membership
        $existing = TripMember::where('trip_id', $trip->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $existing) {
            if (is_null($invitation->user_id)) {
                $invitation->update([
                    'user_id' => $user->id,
                ]);
            } else {
                TripMember::create([
                    'trip_id' => $trip->id,
                    'user_id' => $user->id,
                    'role' => $invitation->role,
                ]);
            }
        }

        // Save member preferences if provided
        if (! empty($preferences)) {
            MemberPreference::updateOrCreate(
                [
                    'trip_id' => $trip->id,
                    'user_id' => $user->id,
                ],
                [
                    'interests' => $preferences['interests'] ?? [],
                    'pace' => $preferences['pace'] ?? 'balanced',
                ]
            );
        }

        return $trip;
    }

    /**
     * Remove a member from the trip with leader preservation rule.
     */
    public function removeMember(Trip $trip, User $member, User $actor): bool
    {
        $memberRecord = TripMember::where('trip_id', $trip->id)
            ->where('user_id', $member->id)
            ->first();

        if (! $memberRecord) {
            return false;
        }

        // Leader preservation rule: trip must always retain at least 1 leader
        if ($memberRecord->role === 'leader') {
            $leaderCount = TripMember::where('trip_id', $trip->id)
                ->where('role', 'leader')
                ->count();

            if ($leaderCount <= 1) {
                throw new DomainException(__('The trip must always have at least one leader.'));
            }
        }

        // Delete preference and votes for this user on this trip
        MemberPreference::where('trip_id', $trip->id)
            ->where('user_id', $member->id)
            ->delete();

        $trip->votes()
            ->where('user_id', $member->id)
            ->delete();

        return (bool) $memberRecord->delete();
    }

    /**
     * Calculate group preference harmony, common pace, and interest breakdown.
     */
    public function calculateGroupMood(Trip $trip): array
    {
        $preferences = MemberPreference::where('trip_id', $trip->id)->get();
        $memberCount = $preferences->count();

        if ($memberCount === 0) {
            return [
                'harmony_score' => 100,
                'common_pace' => 'balanced',
                'top_interests' => [],
                'breakdown' => [],
                'member_count' => 0,
            ];
        }

        // Calculate pace frequency
        $paceCounts = ['relaxed' => 0, 'balanced' => 0, 'packed' => 0];
        $tagCounts = [];

        foreach ($preferences as $pref) {
            $pace = $pref->pace ?? 'balanced';
            if (isset($paceCounts[$pace])) {
                $paceCounts[$pace]++;
            }

            $interests = is_array($pref->interests) ? $pref->interests : [];
            foreach ($interests as $tag) {
                $tag = strtolower(trim((string) $tag));
                if ($tag !== '') {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }

        // Determine majority pace
        arsort($paceCounts);
        $commonPace = array_key_first($paceCounts) ?: 'balanced';
        $dominantPaceCount = current($paceCounts);
        $paceAgreementRatio = $dominantPaceCount / $memberCount; // 0.0 to 1.0

        // Calculate tag overlap harmony
        $totalTags = count($tagCounts);
        $tagAgreementRatio = 0.8;

        if ($totalTags > 0 && $memberCount > 1) {
            $sharedTagsCount = 0;
            foreach ($tagCounts as $count) {
                if ($count > 1) {
                    $sharedTagsCount += ($count / $memberCount);
                }
            }
            $tagAgreementRatio = min(1.0, max(0.4, $sharedTagsCount / max(1, $totalTags * 0.5)));
        }

        // Weighted harmony score: 50% pace agreement + 50% interest overlap
        $harmonyScore = (int) round(($paceAgreementRatio * 45) + ($tagAgreementRatio * 45) + 10);
        $harmonyScore = max(40, min(100, $harmonyScore));

        // Format top interests sorted by popularity
        arsort($tagCounts);
        $topInterests = [];
        $breakdown = [];

        foreach ($tagCounts as $tag => $count) {
            $percentage = (int) round(($count / $memberCount) * 100);
            $topInterests[] = [
                'tag' => $tag,
                'count' => $count,
                'percentage' => $percentage,
            ];
            $breakdown[$tag] = $percentage;
        }

        return [
            'harmony_score' => $harmonyScore,
            'common_pace' => $commonPace,
            'top_interests' => array_slice($topInterests, 0, 5),
            'breakdown' => $breakdown,
            'member_count' => $memberCount,
        ];
    }
}
