<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\StoreProposalRequest;
use App\Models\Proposal;
use App\Models\TravelRequest;
use App\Models\Trip;
use App\Services\DynamicBudgetAdvisorService;
use App\Services\ProposalAiOptimizerService;
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
        protected ProposalService $proposalService,
        protected ProposalAiOptimizerService $proposalAiOptimizerService,
        protected DynamicBudgetAdvisorService $dynamicBudgetAdvisorService
    ) {}

    /**
     * Display side-by-side comparison matrix of all proposals for a request with AI Insights.
     */
    public function compare(Request $request, Trip $trip, TravelRequest $travelRequest): View|JsonResponse
    {
        $this->authorize('view', $trip);

        if ($travelRequest->trip_id !== $trip->id) {
            abort(404);
        }

        // Run AI analysis & badge ranking across competing proposals
        $this->proposalAiOptimizerService->compareMultipleProposals($travelRequest);

        $matrix = $this->proposalService->getComparisonMatrix($travelRequest);
        $budgetAdvice = $this->dynamicBudgetAdvisorService->suggestOptimalBookingDates($trip);
        $comboAdvice = $this->dynamicBudgetAdvisorService->generateBundleSuggestions($travelRequest);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $matrix,
                'budget_advice' => $budgetAdvice,
                'combo_advice' => $comboAdvice,
            ]);
        }

        return view('marketplace.compare', compact('trip', 'travelRequest', 'matrix', 'budgetAdvice', 'comboAdvice'));
    }

    /**
     * Trigger on-demand AI re-analysis for a specific proposal.
     */
    public function analyze(Request $request, Trip $trip, Proposal $proposal): JsonResponse
    {
        $this->authorize('view', $trip);

        $insight = $this->proposalAiOptimizerService->analyzeProposal($proposal);

        return response()->json([
            'success' => true,
            'message' => __('Proposal analyzed successfully with AI insights.'),
            'insight' => [
                'score' => $insight->score,
                'badge' => $insight->badge,
                'badge_label' => $insight->badge_label,
                'badge_classes' => $insight->badge_classes,
                'pros' => $insight->pros ?? [],
                'cons' => $insight->cons ?? [],
                'hidden_costs' => $insight->hidden_costs ?? [],
                'summary_verdict' => $insight->summary_verdict,
            ],
        ]);
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
