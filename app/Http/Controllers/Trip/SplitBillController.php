<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Trip;
use App\Services\MinCashFlowService;
use App\Services\SplitBillService;
use App\Services\VietQrService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SplitBillController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SplitBillService $splitBillService,
        protected MinCashFlowService $minCashFlowService,
        protected VietQrService $vietQrService
    ) {}

    /**
     * Get the settlement plan and net balance overview for the trip.
     */
    public function getSettlementPlan(Trip $trip): JsonResponse
    {
        $this->ensureTripMember($trip);

        $plan = $this->minCashFlowService->generateSettlementPlan($trip);
        $banks = $this->vietQrService->getBankList();

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'supported_banks' => $banks,
        ]);
    }

    /**
     * Get participants and payer details for a specific activity.
     */
    public function getActivitySplit(Trip $trip, Activity $activity): JsonResponse
    {
        $this->ensureTripMember($trip);

        if ($activity->trip_id !== $trip->id) {
            abort(404);
        }

        $splitDetails = $this->splitBillService->getActivitySplitDetails($activity);

        // Get all members of the trip for selection
        $allMembers = [];
        if ($trip->owner) {
            $allMembers[] = [
                'id' => $trip->owner->id,
                'name' => $trip->owner->name,
                'avatar' => $trip->owner->avatar,
                'is_owner' => true,
            ];
        }
        foreach ($trip->members()->with('user')->get() as $tm) {
            if ($tm->user && $tm->user->id !== $trip->owner_id) {
                $allMembers[] = [
                    'id' => $tm->user->id,
                    'name' => $tm->user->name,
                    'avatar' => $tm->user->avatar,
                    'is_owner' => false,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'split_details' => $splitDetails,
            'trip_members' => $allMembers,
        ]);
    }

    /**
     * Assign participants and payer to an activity.
     */
    public function assignParticipants(Request $request, Trip $trip, Activity $activity): JsonResponse
    {
        $this->authorize('update', $trip);

        if ($activity->trip_id !== $trip->id) {
            abort(404);
        }

        $validated = $request->validate([
            'payer_id' => 'nullable|exists:users,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'custom_amounts' => 'nullable|array',
            'custom_amounts.*' => 'nullable|numeric|min:0',
        ]);

        $userIds = $validated['user_ids'] ?? [];
        $payerId = $validated['payer_id'] ?? null;
        $customAmounts = $validated['custom_amounts'] ?? [];

        $this->splitBillService->assignParticipants($activity, $userIds, $payerId, $customAmounts);

        $updatedDetails = $this->splitBillService->getActivitySplitDetails($activity->fresh());

        return response()->json([
            'success' => true,
            'message' => __('Activity split-bill updated successfully.'),
            'split_details' => $updatedDetails,
        ]);
    }

    /**
     * Generate VietQR Napas URL for a payment.
     */
    public function generateVietQr(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureTripMember($trip);

        $validated = $request->validate([
            'bank_bin' => 'required|string',
            'account_number' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'to_user_id' => 'nullable|exists:users,id',
            'from_user_id' => 'nullable|exists:users,id',
            'account_name' => 'nullable|string',
        ]);

        $memo = $this->vietQrService->formatSettlementMemo(
            $trip->id,
            $validated['from_user_id'] ?? auth()->id(),
            $validated['to_user_id'] ?? null
        );

        $qrUrl = $this->vietQrService->generateQrUrl(
            $validated['bank_bin'],
            $validated['account_number'],
            (float) $validated['amount'],
            $memo,
            $validated['account_name'] ?? ''
        );

        return response()->json([
            'success' => true,
            'qr_url' => $qrUrl,
            'memo' => $memo,
            'amount' => (float) $validated['amount'],
        ]);
    }

    /**
     * Record and confirm settlement payment.
     */
    public function confirmPayment(Request $request, Trip $trip): JsonResponse
    {
        $this->ensureTripMember($trip);

        $validated = $request->validate([
            'from_user_id' => 'required|exists:users,id',
            'to_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'bank_bin' => 'nullable|string',
            'bank_account_number' => 'nullable|string',
            'note' => 'nullable|string',
            'proof_image' => 'nullable|image|max:5120',
        ]);

        $proofImagePath = null;
        if ($request->hasFile('proof_image')) {
            $proofImagePath = $request->file('proof_image')->store('settlements', 'public');
        }

        $payment = $this->vietQrService->createOrUpdateSettlementPayment(
            $trip,
            (int) $validated['from_user_id'],
            (int) $validated['to_user_id'],
            (float) $validated['amount'],
            $validated['bank_bin'] ?? null,
            $validated['bank_account_number'] ?? null,
            $validated['note'] ?? null
        );

        $this->vietQrService->confirmPayment($payment, $proofImagePath);

        return response()->json([
            'success' => true,
            'message' => __('Settlement payment confirmed successfully.'),
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Ensure the authenticated user is a member or owner of the trip.
     */
    protected function ensureTripMember(Trip $trip): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if ($trip->owner_id === $user->id) {
            return;
        }

        $isMember = $trip->members()->where('user_id', $user->id)->exists();
        if (! $isMember) {
            abort(403, __('You are not a member of this trip.'));
        }
    }
}
