<?php

namespace App\Http\Controllers\Trip;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Services\VotingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VoteController extends Controller
{
    protected VotingService $votingService;

    public function __construct(VotingService $votingService)
    {
        $this->votingService = $votingService;
    }

    /**
     * Get candidates list with voting state for the current trip.
     */
    public function index(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $candidates = $this->votingService->getCandidatePool($trip, auth()->user());

        return response()->json([
            'success' => true,
            'candidates' => $candidates,
        ]);
    }

    /**
     * Cast or update a vote (+1 or -1) for an activity, POI, or service.
     */
    public function store(Request $request, Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $validated = $request->validate([
            'votable_type' => ['required', 'string'],
            'votable_id' => ['required', 'integer'],
            'value' => ['required', 'integer', 'in:1,-1'],
        ]);

        $vote = $this->votingService->castVote(
            $trip,
            auth()->user(),
            $validated['votable_type'],
            $validated['votable_id'],
            $validated['value']
        );

        $candidates = $this->votingService->getCandidatePool($trip, auth()->user());
        $updatedCandidate = $candidates->first(function ($c) use ($validated) {
            return $c->votable_type === $validated['votable_type']
                && (int) $c->votable_id === (int) $validated['votable_id'];
        });

        return response()->json([
            'success' => true,
            'message' => __('Vote recorded successfully.'),
            'vote' => $vote,
            'candidate' => $updatedCandidate,
            'candidates' => $candidates,
        ]);
    }

    /**
     * Get voting leaderboard ranked by net score.
     */
    public function leaderboard(Trip $trip): JsonResponse
    {
        Gate::authorize('view', $trip);

        $leaderboard = $this->votingService->getVotingLeaderboard($trip, auth()->user());

        return response()->json([
            'success' => true,
            'leaderboard' => $leaderboard,
        ]);
    }
}
