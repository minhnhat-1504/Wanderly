<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SplitBillService
{
    /**
     * Assign participants and payer for a specific activity.
     *
     * @param  array<int>  $userIds
     * @param  array<int, float|null>  $customAmounts
     */
    public function assignParticipants(
        Activity $activity,
        array $userIds,
        ?int $payerId = null,
        array $customAmounts = []
    ): void {
        DB::transaction(function () use ($activity, $userIds, $payerId, $customAmounts) {
            // Update payer on the activity
            $activity->update(['payer_id' => $payerId]);

            // Sync participants
            if (empty($userIds)) {
                $activity->participants()->delete();

                return;
            }

            // Remove participants who are no longer in the list
            $activity->participants()->whereNotIn('user_id', $userIds)->delete();

            // Insert or update participants
            foreach ($userIds as $userId) {
                $customAmount = isset($customAmounts[$userId]) && $customAmounts[$userId] !== null && $customAmounts[$userId] !== ''
                    ? (float) $customAmounts[$userId]
                    : null;

                ActivityParticipant::updateOrCreate(
                    [
                        'activity_id' => $activity->id,
                        'user_id' => $userId,
                    ],
                    [
                        'custom_amount' => $customAmount,
                    ]
                );
            }
        });
    }

    /**
     * Calculate net balance for each member in a trip.
     * Net Balance(u) = Total Spent(u) - Total Owed(u)
     *
     * @return array{
     *     members: array<int, array>,
     *     creditors: array<int, array>,
     *     debtors: array<int, array>,
     *     total_split_cost: float,
     *     is_settled: bool
     * }
     */
    public function calculateTripBalances(Trip $trip): array
    {
        // 1. Collect all trip members (owner + invited members)
        $membersMap = [];

        if ($trip->owner) {
            $membersMap[$trip->owner->id] = [
                'user_id' => $trip->owner->id,
                'name' => $trip->owner->name,
                'email' => $trip->owner->email,
                'avatar' => $trip->owner->avatar,
                'role' => 'leader',
                'total_spent' => 0.00,
                'total_owed' => 0.00,
                'net_balance' => 0.00,
                'status' => 'settled',
            ];
        }

        $tripMembers = $trip->members()->with('user')->get();
        foreach ($tripMembers as $tm) {
            if ($tm->user) {
                $membersMap[$tm->user->id] = [
                    'user_id' => $tm->user->id,
                    'name' => $tm->user->name,
                    'email' => $tm->user->email,
                    'avatar' => $tm->user->avatar,
                    'role' => $tm->role ?? 'member',
                    'total_spent' => 0.00,
                    'total_owed' => 0.00,
                    'net_balance' => 0.00,
                    'status' => 'settled',
                ];
            }
        }

        // 2. Fetch all activities with cost and participants
        $activities = $trip->activities()
            ->where('cost', '>', 0)
            ->with(['participants.user', 'payer'])
            ->get();

        $totalSplitCost = 0.00;

        foreach ($activities as $activity) {
            $cost = (float) $activity->cost;
            $payerId = $activity->payer_id;
            $participants = $activity->participants;

            // Only process activities that have split participants or a designated payer
            if (! $payerId && $participants->isEmpty()) {
                continue;
            }

            $totalSplitCost += $cost;

            // Credit the payer
            if ($payerId) {
                if (! isset($membersMap[$payerId])) {
                    $payerUser = $activity->payer ?? User::find($payerId);
                    if ($payerUser) {
                        $membersMap[$payerId] = [
                            'user_id' => $payerUser->id,
                            'name' => $payerUser->name,
                            'email' => $payerUser->email,
                            'avatar' => $payerUser->avatar,
                            'role' => 'member',
                            'total_spent' => 0.00,
                            'total_owed' => 0.00,
                            'net_balance' => 0.00,
                            'status' => 'settled',
                        ];
                    }
                }
                if (isset($membersMap[$payerId])) {
                    $membersMap[$payerId]['total_spent'] += $cost;
                }
            }

            // Debit the participants
            if ($participants->isNotEmpty()) {
                $hasCustom = $participants->contains(fn ($p) => $p->custom_amount !== null);

                if ($hasCustom) {
                    foreach ($participants as $p) {
                        $pUserId = $p->user_id;
                        $amount = (float) ($p->custom_amount ?? 0);
                        $this->ensureMemberExists($membersMap, $pUserId, $p->user);
                        $membersMap[$pUserId]['total_owed'] += $amount;
                    }
                } else {
                    $count = $participants->count();
                    $equalShare = round($cost / $count, 2);
                    $allocated = 0.00;

                    foreach ($participants->values() as $index => $p) {
                        $pUserId = $p->user_id;
                        $this->ensureMemberExists($membersMap, $pUserId, $p->user);

                        // If last participant, adjust for any penny rounding discrepancy
                        if ($index === $count - 1) {
                            $share = round($cost - $allocated, 2);
                        } else {
                            $share = $equalShare;
                            $allocated += $share;
                        }

                        $membersMap[$pUserId]['total_owed'] += $share;
                    }
                }
            }
        }

        // 3. Compute Net Balance and classify into Creditors & Debtors
        $creditors = [];
        $debtors = [];
        $isSettled = true;

        foreach ($membersMap as $userId => &$member) {
            $member['total_spent'] = round($member['total_spent'], 2);
            $member['total_owed'] = round($member['total_owed'], 2);
            $net = round($member['total_spent'] - $member['total_owed'], 2);
            $member['net_balance'] = $net;

            if ($net > 0.009) {
                $member['status'] = 'creditor';
                $creditors[] = $member;
                $isSettled = false;
            } elseif ($net < -0.009) {
                $member['status'] = 'debtor';
                $debtors[] = $member;
                $isSettled = false;
            } else {
                $member['status'] = 'settled';
                $member['net_balance'] = 0.00;
            }
        }
        unset($member);

        return [
            'members' => array_values($membersMap),
            'creditors' => $creditors,
            'debtors' => $debtors,
            'total_split_cost' => round($totalSplitCost, 2),
            'is_settled' => $isSettled,
        ];
    }

    /**
     * Get detailed split status for a single activity.
     */
    public function getActivitySplitDetails(Activity $activity): array
    {
        $participants = $activity->participants()->with('user')->get();
        $payer = $activity->payer;
        $cost = (float) $activity->cost;
        $count = $participants->count();

        $participantDetails = [];
        $hasCustom = $participants->contains(fn ($p) => $p->custom_amount !== null);

        foreach ($participants as $index => $p) {
            $share = $hasCustom
                ? (float) ($p->custom_amount ?? 0)
                : ($count > 0 ? round($cost / $count, 2) : 0);

            $participantDetails[] = [
                'user_id' => $p->user_id,
                'name' => $p->user->name ?? __('Unknown'),
                'avatar' => $p->user->avatar ?? null,
                'custom_amount' => $p->custom_amount,
                'calculated_share' => $share,
            ];
        }

        return [
            'activity_id' => $activity->id,
            'cost' => $cost,
            'payer_id' => $activity->payer_id,
            'payer' => $payer ? [
                'id' => $payer->id,
                'name' => $payer->name,
                'avatar' => $payer->avatar,
            ] : null,
            'participants_count' => $count,
            'is_split' => $count > 0,
            'is_custom' => $hasCustom,
            'participants' => $participantDetails,
        ];
    }

    /**
     * Ensure member is present in membersMap.
     */
    private function ensureMemberExists(array &$membersMap, int $userId, ?User $user = null): void
    {
        if (! isset($membersMap[$userId])) {
            $u = $user ?? User::find($userId);
            $membersMap[$userId] = [
                'user_id' => $userId,
                'name' => $u->name ?? __('Unknown'),
                'email' => $u->email ?? '',
                'avatar' => $u->avatar ?? null,
                'role' => 'member',
                'total_spent' => 0.00,
                'total_owed' => 0.00,
                'net_balance' => 0.00,
                'status' => 'settled',
            ];
        }
    }
}
