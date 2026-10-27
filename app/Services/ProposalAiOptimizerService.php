<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalInsight;
use App\Models\TravelRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProposalAiOptimizerService
{
    /**
     * Analyze a proposal, detecting pros, cons, hidden costs, score, and verdict.
     */
    public function analyzeProposal(Proposal $proposal, ?TravelRequest $request = null): ProposalInsight
    {
        $proposal->loadMissing(['items.service', 'provider', 'request']);
        $request = $request ?? $proposal->request;

        $items = $proposal->items;
        $allSpecs = [];
        $combinedText = Str::lower($proposal->message ?? '');

        foreach ($items as $item) {
            $combinedText .= ' '.Str::lower($item->name);
            if (is_array($item->specs)) {
                $allSpecs[] = $item->specs;
                foreach ($item->specs as $k => $v) {
                    $combinedText .= ' '.Str::lower((string) $k).' '.Str::lower((string) $v);
                }
            }
        }

        $hiddenCosts = $this->detectHiddenCosts($allSpecs, $combinedText);
        $pros = $this->detectPros($proposal, $allSpecs, $combinedText);
        $cons = $this->detectCons($proposal, $allSpecs, $combinedText, $hiddenCosts);
        $score = $this->calculateScore($proposal, $request, $pros, $cons, $hiddenCosts);
        $verdict = $this->generateVerdict($proposal, $pros, $cons, $hiddenCosts, $score);

        return ProposalInsight::updateOrCreate(
            ['proposal_id' => $proposal->id],
            [
                'pros' => array_values(array_unique($pros)),
                'cons' => array_values(array_unique($cons)),
                'hidden_costs' => array_values(array_unique($hiddenCosts)),
                'summary_verdict' => $verdict,
                'score' => $score,
            ]
        );
    }

    /**
     * Compare all proposals belonging to a travel request, rank them,
     * and assign AI badges: Best Value, Budget Saver, and Premium Choice.
     *
     * @return Collection<int, Proposal>
     */
    public function compareMultipleProposals(TravelRequest $request): Collection
    {
        $proposals = $request->proposals()
            ->with(['provider', 'items.service', 'insight'])
            ->whereIn('status', ['pending', 'accepted'])
            ->get();

        if ($proposals->isEmpty()) {
            return collect();
        }

        // Ensure every proposal has an insight analyzed
        foreach ($proposals as $proposal) {
            if (! $proposal->insight) {
                $insight = $this->analyzeProposal($proposal, $request);
                $proposal->setRelation('insight', $insight);
            }
        }

        if ($proposals->count() === 1) {
            $single = $proposals->first();
            $single->insight->update(['badge' => ProposalInsight::BADGE_BEST_VALUE]);

            return $proposals;
        }

        // Find candidate for Budget Saver: strictly lowest total_price
        $cheapest = $proposals->sortBy('total_price')->first();

        // Find candidate for Premium Choice: highest score or highest specs richness
        $highestScore = $proposals->sortByDesc(fn (Proposal $p) => $p->insight->score ?? 0)->first();

        // Calculate Value Ratio = Score / Price for Best Value
        $bestValue = $proposals->sortByDesc(function (Proposal $p) {
            $price = max((float) $p->total_price, 1.0);
            $score = (float) ($p->insight->score ?? 70);

            return $score / $price;
        })->first();

        foreach ($proposals as $proposal) {
            $badge = null;

            if ($proposal->id === $bestValue->id) {
                $badge = ProposalInsight::BADGE_BEST_VALUE;
            } elseif ($proposal->id === $cheapest->id) {
                $badge = ProposalInsight::BADGE_BUDGET_SAVER;
            } elseif ($proposal->id === $highestScore->id) {
                $badge = ProposalInsight::BADGE_PREMIUM_CHOICE;
            }

            if ($proposal->insight->badge !== $badge) {
                $proposal->insight->update(['badge' => $badge]);
            }
        }

        return $proposals->fresh(['insight', 'provider', 'items.service']);
    }

    /**
     * Identify potential extra or unincluded expenses.
     */
    public function detectHiddenCosts(array $allSpecs, string $text): array
    {
        $hiddenCosts = [];

        // 1. Entrance fees / tickets
        if (! str_contains($text, 'ticket included') && ! str_contains($text, 'bao gồm vé') && ! str_contains($text, 'all entrance')) {
            if (str_contains($text, 'tour') || str_contains($text, 'sightseeing') || str_contains($text, 'attraction') || str_contains($text, 'tham quan')) {
                $hiddenCosts[] = __('Entrance tickets to tourist destinations not included (~100,000 - 150,000 VND / pax)');
            }
        }

        // 2. Driver tip & gratuities
        if (! str_contains($text, 'tip included') && ! str_contains($text, 'đã gồm tip')) {
            if (str_contains($text, 'driver') || str_contains($text, 'tài xế') || str_contains($text, 'guide') || str_contains($text, 'xe')) {
                $hiddenCosts[] = __('Driver tip and gratuities are customary and not included');
            }
        }

        // 3. Toll fees / Fuel surcharges
        if (str_contains($text, 'gas excluded') || str_contains($text, 'chưa gồm xăng') || str_contains($text, 'chưa bao gồm phí cầu đường')) {
            $hiddenCosts[] = __('Highway toll fees and fuel surcharges not included in quoted price');
        } elseif ((str_contains($text, 'car') || str_contains($text, 'shuttle') || str_contains($text, 'xe')) && ! str_contains($text, 'toll included') && ! str_contains($text, 'bao gồm cầu đường')) {
            $hiddenCosts[] = __('Verify highway toll and parking fees with driver before departure');
        }

        // 4. Meals & personal expenses
        if (str_contains($text, 'meal excluded') || str_contains($text, 'tự túc ăn uống') || str_contains($text, 'meals not included')) {
            $hiddenCosts[] = __('Personal meals and refreshments are self-funded by traveler');
        }

        return $hiddenCosts;
    }

    /**
     * Detect positive highlights, amenities, and value propositions.
     */
    protected function detectPros(Proposal $proposal, array $allSpecs, string $text): array
    {
        $pros = [];

        // Provider rating
        if ($proposal->provider && (float) $proposal->provider->rating >= 4.8) {
            $pros[] = __('Top-rated verified provider with :rating/5.0 traveler satisfaction', ['rating' => $proposal->provider->rating]);
        }

        // Transport specs
        if (str_contains($text, '7-seat') || str_contains($text, '7 chỗ')) {
            $pros[] = __('Spacious 7-seater vehicle with dedicated luggage capacity');
        }
        if (str_contains($text, 'ac') || str_contains($text, 'air-conditioning') || str_contains($text, 'điều hòa')) {
            $pros[] = __('Equipped with high-performance modern air conditioning');
        }
        if (str_contains($text, 'english') || str_contains($text, 'song ngữ')) {
            $pros[] = __('English-speaking friendly driver/guide available');
        }

        // Perks
        if (str_contains($text, 'airport') || str_contains($text, 'sân bay')) {
            $pros[] = __('Door-to-door direct airport pickup & drop-off');
        }
        if (str_contains($text, 'water') || str_contains($text, 'nước suối') || str_contains($text, 'towel')) {
            $pros[] = __('Complimentary chilled mineral water and refreshing towels');
        }
        if (str_contains($text, 'coffee') || str_contains($text, 'tasting')) {
            $pros[] = __('Includes authentic local specialty coffee tasting experience');
        }

        if (empty($pros)) {
            $pros[] = __('Transparent quote directly from local Da Lat verified partner');
            $pros[] = __('Direct 1-1 chat support with provider throughout the trip');
        }

        return $pros;
    }

    /**
     * Detect potential limitations or trade-offs.
     */
    protected function detectCons(Proposal $proposal, array $allSpecs, string $text, array $hiddenCosts): array
    {
        $cons = [];

        if (count($hiddenCosts) >= 3) {
            $cons[] = __('Several external expenses are not bundled into the base quote');
        }

        if (str_contains($text, 'strict') || str_contains($text, 'no refund') || str_contains($text, 'không hoàn')) {
            $cons[] = __('Strict cancellation policy applies; advance notice required for changes');
        }

        if (str_contains($text, 'guide excluded') || str_contains($text, 'không có hướng dẫn')) {
            $cons[] = __('Self-guided itinerary; professional tour guide is not included');
        }

        if (empty($cons)) {
            $cons[] = __('Ensure departure time is coordinated at least 24 hours prior via in-app chat');
        }

        return $cons;
    }

    /**
     * Compute a holistic score from 50 to 98.
     */
    protected function calculateScore(Proposal $proposal, ?TravelRequest $request, array $pros, array $cons, array $hiddenCosts): int
    {
        $score = 82;

        // Provider rating bonus (up to +8 points)
        if ($proposal->provider) {
            $rating = (float) $proposal->provider->rating;
            if ($rating >= 4.9) {
                $score += 6;
            } elseif ($rating >= 4.5) {
                $score += 3;
            }
        }

        // Budget alignment
        if ($request && (float) $request->max_budget > 0) {
            $ratio = (float) $proposal->total_price / (float) $request->max_budget;
            if ($ratio <= 0.8) {
                $score += 6; // Excellent savings
            } elseif ($ratio <= 1.0) {
                $score += 2; // Within budget
            } else {
                $score -= 8; // Over budget
            }
        }

        // Pros bonus & Cons/Hidden cost penalties
        $score += min(count($pros) * 2, 8);
        $score -= min(count($hiddenCosts) * 2, 6);
        $score -= min(count($cons) * 2, 4);

        return (int) max(50, min(98, $score));
    }

    /**
     * Generate an AI-styled crisp verdict text.
     */
    protected function generateVerdict(Proposal $proposal, array $pros, array $cons, array $hiddenCosts, int $score): string
    {
        if ($score >= 90) {
            return __('Exceptional proposal offering premium convenience, high traveler ratings, and strong overall service value.');
        }

        if ($score >= 80) {
            return __('A well-balanced competitive proposal that aligns comfortably with your itinerary and group expectations.');
        }

        if (! empty($hiddenCosts)) {
            return __('Affordable base rate, though we recommend budgeting extra for unincluded attraction fees and driver gratuities.');
        }

        return __('Standard local offering. Review the included service specs and confirm logistics via in-app chat before booking.');
    }
}
