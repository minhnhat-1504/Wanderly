<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Facades\Schema;

class BudgetService
{
    /**
     * Calculate comprehensive budget status for a given trip.
     *
     * @return array{
     *     budget: float,
     *     activities_cost: float,
     *     room_cost: float,
     *     total_spent: float,
     *     remaining_budget: float,
     *     percentage: float,
     *     status: string
     * }
     */
    public function calculateBudgetStatus(Trip $trip): array
    {
        $budget = (float) $trip->budget;

        // Calculate activities cost safely (if activities table exists and model is available)
        $activitiesCost = 0.00;
        if (Schema::hasTable('activities')) {
            $activitiesCost = (float) $trip->activities()->sum('cost');
        }

        // Calculate room cost for rooms where is_budget_included is true
        $roomCost = (float) $trip->roomBookings()
            ->where('is_budget_included', true)
            ->sum('price');

        $totalSpent = round($activitiesCost + $roomCost, 2);
        $remainingBudget = round($budget - $totalSpent, 2);

        $percentage = 0.0;
        if ($budget > 0) {
            $percentage = round(($totalSpent / $budget) * 100, 2);
        } elseif ($totalSpent > 0) {
            $percentage = 100.0;
        }

        $status = 'normal';
        if ($percentage > 95) {
            $status = 'danger';
        } elseif ($percentage >= 80) {
            $status = 'warning';
        }

        return [
            'budget' => $budget,
            'activities_cost' => $activitiesCost,
            'room_cost' => $roomCost,
            'total_spent' => $totalSpent,
            'remaining_budget' => $remainingBudget,
            'percentage' => $percentage,
            'status' => $status,
        ];
    }

    /**
     * Determine if a new total spent amount crosses warning (>= 80%) or danger (> 95%) threshold.
     */
    public function checkThreshold(Trip $trip, float $newTotalSpent): ?string
    {
        $budget = (float) $trip->budget;
        if ($budget <= 0) {
            return $newTotalSpent > 0 ? 'danger' : null;
        }

        $percentage = ($newTotalSpent / $budget) * 100;

        if ($percentage > 95) {
            return 'danger';
        }

        if ($percentage >= 80) {
            return 'warning';
        }

        return null;
    }
}
