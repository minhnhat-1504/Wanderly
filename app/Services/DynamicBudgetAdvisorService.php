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

        // Find candidate providers
        $candidateProviders = collect();
        if ($provider) {
            $candidateProviders->push($provider);
        } else {
            $proposalProviders = Provider::whereHas('proposals.request', fn ($q) => $q->where('trip_id', $trip->id))->get();
            if ($proposalProviders->isNotEmpty()) {
                $candidateProviders = $proposalProviders;
            } else {
                $destinationName = $trip->destination;
                if ($destinationName) {
                    $candidateProviders = Provider::whereHas('destination', fn ($q) => $q->where('name', 'LIKE', '%'.$destinationName.'%'))->get();
                }
            }
        }

        // Search for real weekday discounts in submitted proposals and provider services
        $bestDeal = null;

        // 1. Check proposals submitted for this trip's requests
        $travelRequests = $trip->travelRequests()->with(['proposals.items', 'proposals.provider'])->get();
        foreach ($travelRequests as $req) {
            foreach ($req->proposals as $prop) {
                foreach ($prop->items as $item) {
                    $deal = $this->extractWeekdayDiscount($item->name, (float) $item->price, $item->specs, $prop->provider?->business_name);
                    if ($deal && (! $bestDeal || $deal['savings'] > $bestDeal['savings'])) {
                        $bestDeal = $deal;
                    }
                }
            }
        }

        // 2. Check catalog services of candidate providers
        if (! $bestDeal) {
            foreach ($candidateProviders as $cand) {
                $services = $cand->services()->get();
                foreach ($services as $srv) {
                    $deal = $this->extractWeekdayDiscount($srv->name, (float) $srv->price, $srv->specs, $cand->business_name);
                    if ($deal && (! $bestDeal || $deal['savings'] > $bestDeal['savings'])) {
                        $bestDeal = $deal;
                    }
                }
            }
        }

        // If no providers offer real weekday discounts, do not invent mock savings
        if (! $bestDeal) {
            return [
                'has_suggestion' => false,
                'message' => __('No verified partner weekday discounts available for these dates.'),
            ];
        }

        $durationDays = max(1, $startDate->diffInDays($endDate) + 1);

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
            'estimated_savings_vnd' => $bestDeal['savings'],
            'savings_percentage' => $bestDeal['percentage'],
            'headline' => __(':provider Weekday Discount: Save up to :percent%', [
                'provider' => $bestDeal['provider_name'],
                'percent' => $bestDeal['percentage'],
            ]),
            'message' => __(':provider offers a lower weekday rate for :service (:weekday VND vs :standard VND). Shifting your trip to mid-week (:dates) saves :amount VND.', [
                'provider' => $bestDeal['provider_name'],
                'service' => $bestDeal['service_name'],
                'weekday' => number_format($bestDeal['weekday_price'], 0, ',', '.'),
                'standard' => number_format($bestDeal['standard_price'], 0, ',', '.'),
                'dates' => $suggestedStart->format('M d').' - '.$suggestedEnd->format('M d'),
                'amount' => number_format($bestDeal['savings'], 0, ',', '.'),
            ]),
        ];
    }

    /**
     * Extract weekday discount from item/service specs if defined.
     *
     * @param  array<string, mixed>|null  $specs
     * @return array{service_name: string, provider_name: string, standard_price: float, weekday_price: float, savings: float, percentage: int}|null
     */
    protected function extractWeekdayDiscount(string $serviceName, float $price, ?array $specs, ?string $providerName): ?array
    {
        if (! $specs || $price <= 0) {
            return null;
        }

        $weekdayPrice = null;
        $savings = 0;

        if (isset($specs['weekday_price']) && is_numeric($specs['weekday_price'])) {
            $wp = (float) $specs['weekday_price'];
            if ($wp > 0 && $wp < $price) {
                $weekdayPrice = $wp;
                $savings = $price - $wp;
            }
        } elseif (isset($specs['weekend_surcharge']) && is_numeric($specs['weekend_surcharge'])) {
            $ws = (float) $specs['weekend_surcharge'];
            if ($ws > 0) {
                $savings = $ws;
                $weekdayPrice = max(0, $price - $ws);
            }
        } elseif (isset($specs['weekday_discount']) && is_numeric($specs['weekday_discount'])) {
            $disc = (float) $specs['weekday_discount'];
            if ($disc > 0 && $disc < 100) {
                $savings = round($price * ($disc / 100), 2);
                $weekdayPrice = $price - $savings;
            }
        }

        if ($savings <= 0 || ! $weekdayPrice) {
            return null;
        }

        $percent = (int) round(($savings / $price) * 100);

        return [
            'service_name' => $serviceName,
            'provider_name' => $providerName ?? __('Local Provider'),
            'standard_price' => $price,
            'weekday_price' => $weekdayPrice,
            'savings' => (float) $savings,
            'percentage' => $percent,
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
