<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Display the budget status for the specified trip.
     */
    public function show(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('viewBudget', $trip);

        $status = $this->budgetService->calculateBudgetStatus($trip);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}
