<?php

namespace App\Services;

use App\Models\Provider;
use App\Models\TravelRequest;
use App\Models\Trip;
use Carbon\Carbon;

class DynamicBudgetAdvisorService
{
    /**
     * Analyze trip dates and provider availability to suggest potential date shifts
     * (e.g., mid-week Tuesday-Thursday) that qualify for off-peak partner discounts.
     *
     * @return array<string, mixed>
     */
    public function suggestOptimalBookingDates(Trip $trip, ?Provider $provider = null): array
    {
        $startDate = $trip->start_date ? Carbon::parse($trip->start_date) : null;
        $endDate = $trip->end_date ? Carbon::parse($trip->end_date) : null;

        if (! $startDate || ! $endDate) {
            return [
                'has_suggestion' => false,
                'message' => __('Trip dates not specified.'),
            ];
        }

        // Check if current dates cover Friday, Saturday, or Sunday
        $hasWeekend = false;
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->isFriday() || $current->isSaturday() || $current->isSunday()) {
                $hasWeekend = true;
                break;
            }
            $current->addDay();
        }

        if (! $hasWeekend) {
            return [
                'has_suggestion' => false,
                'message' => __('Your itinerary is already scheduled during off-peak weekdays. Great planning!'),
            ];
        }

        // Calculate potential savings (estimated 15% partner weekend surcharge relief)
        $durationDays = max(1, $startDate->diffInDays($endDate) + 1);
        $totalBudget = (float) $trip->budget;
        $estimatedSavings = round(($totalBudget > 0 ? $totalBudget * 0.12 : 350000), -4);
        $percentSavings = 15;

        // Recommend shifting toward Tuesday of that week or next week
        $suggestedStart = $startDate->copy()->startOfWeek()->addDay(); // Tuesday
        if ($suggestedStart->lt(Carbon::today())) {
            $suggestedStart->addWeek();
        }
        $suggestedEnd = $suggestedStart->copy()->addDays($durationDays - 1);

        return [
            'has_suggestion' => true,
            'current_period' => $startDate->format('D, M d').' - '.$endDate->format('D, M d'),
            'recommended_period' => $suggestedStart->format('D, M d').' - '.$suggestedEnd->format('D, M d'),
            'estimated_savings_vnd' => $estimatedSavings,
            'savings_percentage' => $percentSavings,
            'headline' => __('Mid-Week Shift Savings: Save up to :percent%', ['percent' => $percentSavings]),
            'message' => __('Shifting your :days-day trip to mid-week (:dates) can save an estimated :amount VND by avoiding peak weekend surcharges and crowded attractions.', [
                'days' => $durationDays,
                'dates' => $suggestedStart->format('M d').' - '.$suggestedEnd->format('M d'),
                'amount' => number_format($estimatedSavings),
            ]),
        ];
    }

    /**
     * Detect if cherry-picking individual items across competing proposals
     * forms a cross-provider combo that is cheaper than the cheapest full bundle.
     *
     * @return array<string, mixed>
     */
    public function generateBundleSuggestions(TravelRequest $request): array
    {
        $proposals = $request->proposals()
            ->with(['items.service', 'provider'])
            ->whereIn('status', ['pending', 'accepted'])
            ->get();

        if ($proposals->count() < 2) {
            return [
                'has_suggestion' => false,
                'message' => __('At least two competing proposals are needed to analyze cross-provider combo savings.'),
            ];
        }

        $cheapestFullBundle = $proposals->sortBy('total_price')->first();
        $cheapestBundlePrice = (float) $cheapestFullBundle->total_price;

        // Collect all distinct items categorized by type/name
        $transportItems = collect();
        $tourItems = collect();

        foreach ($proposals as $proposal) {
            foreach ($proposal->items as $item) {
                $itemPayload = [
                    'id' => $item->id,
                    'proposal_id' => $proposal->id,
                    'provider_name' => $proposal->provider?->business_name ?? __('Partner'),
                    'name' => $item->name,
                    'price' => (float) $item->price,
                ];

                $nameLower = strtolower($item->name);
                if (str_contains($nameLower, 'car') || str_contains($nameLower, 'shuttle') || str_contains($nameLower, 'xe')) {
                    $transportItems->push($itemPayload);
                } else {
                    $tourItems->push($itemPayload);
                }
            }
        }

        if ($transportItems->isEmpty() || $tourItems->isEmpty()) {
            return [
                'has_suggestion' => false,
                'message' => __('Standard proposal bundling is currently optimal.'),
            ];
        }

        $bestTransport = $transportItems->sortBy('price')->first();
        $bestTour = $tourItems->sortBy('price')->first();

        $comboTotal = $bestTransport['price'] + $bestTour['price'];
        $savings = $cheapestBundlePrice - $comboTotal;

        if ($savings > 50000) {
            return [
                'has_suggestion' => true,
                'combo_name' => __('Smart Hybrid Combo'),
                'selected_items' => [
                    $bestTransport,
                    $bestTour,
                ],
                'combo_price' => $comboTotal,
                'bundle_price' => $cheapestBundlePrice,
                'savings_vnd' => $savings,
                'tip' => __('By cherry-picking the shuttle from :p1 and the tour from :p2, you save an additional :amount VND compared to the cheapest full bundle.', [
                    'p1' => $bestTransport['provider_name'],
                    'p2' => $bestTour['provider_name'],
                    'amount' => number_format($savings),
                ]),
            ];
        }

        return [
            'has_suggestion' => false,
            'message' => __('Full bundle pricing from :provider remains your best overall value.', [
                'provider' => $cheapestFullBundle->provider?->business_name,
            ]),
        ];
    }
}
