<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomBookingRequest;
use App\Http\Requests\Room\UpdateRoomBookingRequest;
use App\Models\RoomBooking;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Services\RoomBookingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoomBookingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected RoomBookingService $roomService,
        protected BudgetService $budgetService
    ) {}

    /**
     * Store a newly created room booking in storage.
     */
    public function store(StoreRoomBookingRequest $request, Trip $trip): JsonResponse|RedirectResponse
    {
        $this->authorize('manageRooms', $trip);

        $room = $this->roomService->create($trip, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Room booking added successfully.'),
                'data' => $room,
                'budget' => $this->budgetService->calculateBudgetStatus($trip),
            ], 201);
        }

        return redirect()->back()->with('success', __('Room booking added successfully.'));
    }

    /**
     * Update the specified room booking in storage.
     */
    public function update(UpdateRoomBookingRequest $request, RoomBooking $room): JsonResponse|RedirectResponse
    {
        $this->authorize('manageRooms', $room->trip);

        $this->roomService->update($room, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Room booking updated successfully.'),
                'data' => $room->fresh(),
                'budget' => $this->budgetService->calculateBudgetStatus($room->trip),
            ]);
        }

        return redirect()->back()->with('success', __('Room booking updated successfully.'));
    }

    /**
     * Remove the specified room booking from storage.
     */
    public function destroy(Request $request, RoomBooking $room): JsonResponse|RedirectResponse
    {
        $trip = $room->trip;
        $this->authorize('manageRooms', $trip);

        $this->roomService->delete($room);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Room booking removed successfully.'),
                'budget' => $this->budgetService->calculateBudgetStatus($trip),
            ]);
        }

        return redirect()->back()->with('success', __('Room booking removed successfully.'));
    }

    /**
     * Toggle whether the room price is included in the trip budget.
     */
    public function toggleBudget(Request $request, RoomBooking $room): JsonResponse
    {
        $this->authorize('manageRooms', $room->trip);

        $included = $this->roomService->toggleBudgetInclusion($room);
        $budgetStatus = $this->budgetService->calculateBudgetStatus($room->trip);

        return response()->json([
            'success' => true,
            'is_budget_included' => $included,
            'budget' => $budgetStatus,
            'message' => $included
                ? __('Room price is now included in the budget.')
                : __('Room price is excluded from the budget.'),
        ]);
    }
}
