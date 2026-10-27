<?php

namespace App\Services;

use App\Models\SettlementPayment;
use App\Models\Trip;

class MinCashFlowService
{
    public function __construct(
        protected SplitBillService $splitBillService
    ) {}

    /**
     * Generate an optimal, minimized cash flow settlement plan for a trip.
     * Uses Greedy Min-Cash-Flow algorithm to simplify pairwise debts into at most N-1 transactions.
     *
     * @return array{
     *     trip_id: int,
     *     trip_name: string,
     *     is_settled: bool,
     *     total_transactions: int,
     *     total_settlement_amount: float,
     *     transactions: array<int, array>,
     *     members_summary: array<int, array>
     * }
     */
    public function generateSettlementPlan(Trip $trip): array
    {
        $balanceData = $this->splitBillService->calculateTripBalances($trip);
        $members = $balanceData['members'];

        // Separate into positive net balances (creditors) and negative net balances (debtors)
        $creditors = [];
        $debtors = [];

        foreach ($members as $member) {
            $net = $member['net_balance'];
            if ($net > 0.009) {
                $creditors[] = [
                    'user_id' => $member['user_id'],
                    'name' => $member['name'],
                    'avatar' => $member['avatar'],
                    'credit' => $net,
                ];
            } elseif ($net < -0.009) {
                $debtors[] = [
                    'user_id' => $member['user_id'],
                    'name' => $member['name'],
                    'avatar' => $member['avatar'],
                    'debt' => abs($net),
                ];
            }
        }

        // Fetch existing settlement records for this trip
        $existingPayments = SettlementPayment::where('trip_id', $trip->id)->get();

        $transactions = [];

        // Greedy loop: pair the largest debtor with the largest creditor
        while (! empty($creditors) && ! empty($debtors)) {
            // Sort creditors descending by credit
            usort($creditors, fn ($a, $b) => $b['credit'] <=> $a['credit']);
            // Sort debtors descending by debt
            usort($debtors, fn ($a, $b) => $b['debt'] <=> $a['debt']);

            $maxCreditor = &$creditors[0];
            $maxDebtor = &$debtors[0];

            $settleAmount = round(min($maxCreditor['credit'], $maxDebtor['debt']), 2);

            if ($settleAmount > 0.009) {
                // Check if there is an existing payment record
                $existing = $existingPayments->first(function ($p) use ($maxDebtor, $maxCreditor) {
                    return $p->from_user_id === $maxDebtor['user_id']
                        && $p->to_user_id === $maxCreditor['user_id'];
                });

                $transactions[] = [
                    'from_user_id' => $maxDebtor['user_id'],
                    'from_user_name' => $maxDebtor['name'],
                    'from_user_avatar' => $maxDebtor['avatar'],
                    'to_user_id' => $maxCreditor['user_id'],
                    'to_user_name' => $maxCreditor['name'],
                    'to_user_avatar' => $maxCreditor['avatar'],
                    'amount' => $settleAmount,
                    'formatted_amount' => number_format($settleAmount, 0, ',', '.').' VND',
                    'payment_id' => $existing?->id,
                    'status' => $existing?->status ?? 'pending',
                    'proof_image' => $existing?->proof_image,
                    'completed_at' => $existing?->completed_at?->format('d/m/Y H:i'),
                ];

                $maxCreditor['credit'] = round($maxCreditor['credit'] - $settleAmount, 2);
                $maxDebtor['debt'] = round($maxDebtor['debt'] - $settleAmount, 2);
            }

            if ($maxCreditor['credit'] <= 0.009) {
                array_shift($creditors);
            }
            if ($maxDebtor['debt'] <= 0.009) {
                array_shift($debtors);
            }
        }

        $totalSettlementAmount = round(array_sum(array_column($transactions, 'amount')), 2);

        return [
            'trip_id' => $trip->id,
            'trip_name' => $trip->name,
            'is_settled' => empty($transactions),
            'total_transactions' => count($transactions),
            'total_settlement_amount' => $totalSettlementAmount,
            'transactions' => $transactions,
            'members_summary' => $members,
        ];
    }
}
