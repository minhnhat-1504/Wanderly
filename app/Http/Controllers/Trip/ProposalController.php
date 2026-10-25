<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreProposalRequest;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Services\ProposalService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProposalController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ProposalService $proposalService
    ) {}

    /**
     * Display side-by-side comparison matrix of all proposals for a request.
     */
    public function compare(Request $request, Trip $trip, TravelRequest $travelRequest): View|JsonResponse
    {
        $this->authorize('view', $trip);

        if ($travelRequest->trip_id !== $trip->id) {
            abort(404);
        }

        $matrix = $this->proposalService->getComparisonMatrix($travelRequest);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $matrix,
            ]);
        }

        return view('marketplace.compare', compact('trip', 'travelRequest', 'matrix'));
    }

    /**
     * Show form for a provider to create and submit a proposal for a request.
     */
    public function create(Request $request, TravelRequest $travelRequest): View
    {
        $user = $request->user();
        $provider = $user?->provider;

        if (! $provider) {
            abort(403, __('Only registered providers can submit proposals.'));
        }

        return view('marketplace.proposal-create', compact('travelRequest', 'provider'));
    }

    /**
     * Store a newly created proposal from a provider.
     */
    public function store(StoreProposalRequest $request, TravelRequest $travelRequest): RedirectResponse|JsonResponse
    {
        $provider = $request->user()->provider;

        $proposal = $this->proposalService->submitProposal(
            $provider,
            $travelRequest,
            $request->validated()
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('Your proposal has been submitted successfully!'),
                'data' => $proposal,
            ], 201);
        }

        return redirect()->back()
            ->with('success', __('Your proposal has been submitted successfully!'));
    }
}
