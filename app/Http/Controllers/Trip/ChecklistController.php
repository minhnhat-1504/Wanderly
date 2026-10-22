<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checklist\ApplyChecklistTemplateRequest;
use App\Http\Requests\Checklist\StoreChecklistItemRequest;
use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Services\ChecklistService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ChecklistService $checklistService
    ) {}

    /**
     * Get all checklist items and progress for a trip.
     */
    public function index(Request $request, Trip $trip): JsonResponse
    {
        $this->authorize('view', $trip);

        $items = $this->checklistService->getItems($trip);
        $progress = $this->checklistService->calculateProgress($trip);

        return response()->json([
            'success' => true,
            'items' => $items,
            'progress' => $progress,
        ]);
    }

    /**
     * Store a newly created checklist item.
     */
    public function store(StoreChecklistItemRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $item = $this->checklistService->createItem($trip, $request->validated());
        $progress = $this->checklistService->calculateProgress($trip);

        return response()->json([
            'success' => true,
            'message' => __('Item added successfully.'),
            'item' => $item,
            'progress' => $progress,
        ], 201);
    }

    /**
     * Toggle completion status of a checklist item.
     */
    public function toggle(Trip $trip, ChecklistItem $item): JsonResponse
    {
        $this->authorize('update', $trip);

        $updatedItem = $this->checklistService->toggleItem($item);
        $progress = $this->checklistService->calculateProgress($trip);

        return response()->json([
            'success' => true,
            'item' => $updatedItem,
            'progress' => $progress,
        ]);
    }

    /**
     * Remove the specified checklist item.
     */
    public function destroy(Trip $trip, ChecklistItem $item): JsonResponse
    {
        $this->authorize('update', $trip);

        $this->checklistService->deleteItem($item);
        $progress = $this->checklistService->calculateProgress($trip);

        return response()->json([
            'success' => true,
            'message' => __('Item deleted successfully.'),
            'progress' => $progress,
        ]);
    }

    /**
     * Apply a preset checklist template.
     */
    public function applyTemplate(ApplyChecklistTemplateRequest $request, Trip $trip): JsonResponse
    {
        $this->authorize('update', $trip);

        $items = $this->checklistService->applyTemplate($trip, $request->validated('template'));
        $progress = $this->checklistService->calculateProgress($trip);

        return response()->json([
            'success' => true,
            'message' => __('Checklist template applied successfully.'),
            'items' => $items,
            'progress' => $progress,
        ]);
    }
}
