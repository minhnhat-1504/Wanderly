<?php

namespace App\Listeners;

use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Support\Facades\Log;

class RecomputeTripBudget
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        /** @var Trip|null $trip */
        $trip = null;

        if (isset($event->trip) && $event->trip instanceof Trip) {
            $trip = $event->trip;
        } elseif (isset($event->roomBooking) && $event->roomBooking->trip) {
            $trip = $event->roomBooking->trip;
        }

        if ($trip) {
            $status = $this->budgetService->calculateBudgetStatus($trip);

            Log::info("Recomputed budget for Trip ID: {$trip->id}", [
                'trip_id' => $trip->id,
                'total_spent' => $status['total_spent'],
                'percentage' => $status['percentage'],
                'status' => $status['status'],
            ]);
        }
    }
}
