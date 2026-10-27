<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Provider;
use App\Models\TravelRequest;
use DomainException;
use Illuminate\Support\Facades\DB;

class ProposalService
{
    /**
     * Submit a new proposal from a provider for a travel request.
     *
     * @param  array{
     *     message: string,
     *     valid_until: string|\DateTimeInterface,
     *     total_price?: ?float,
     *     items: array<array{name: string, price: float, service_id?: ?int, specs?: ?array}>
     * }  $data
     */
    public function submitProposal(Provider $provider, TravelRequest $request, array $data): Proposal
    {
        return DB::transaction(function () use ($provider, $request, $data) {
            $items = $data['items'] ?? [];
            $computedTotalPrice = 0.00;

            foreach ($items as $item) {
                $computedTotalPrice += (float) ($item['price'] ?? 0.00);
            }

            $totalPrice = isset($data['total_price']) && (float) $data['total_price'] > 0
                ? (float) $data['total_price']
                : $computedTotalPrice;

            $proposal = Proposal::create([
                'request_id' => $request->id,
                'provider_id' => $provider->id,
                'total_price' => $totalPrice,
                'message' => $data['message'],
                'valid_until' => $data['valid_until'],
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                ProposalItem::create([
                    'proposal_id' => $proposal->id,
                    'service_id' => $item['service_id'] ?? null,
                    'name' => $item['name'],
                    'price' => (float) ($item['price'] ?? 0.00),
                    'specs' => $item['specs'] ?? null,
                    'is_accepted' => false,
                ]);
            }

            if ($request->status === 'open') {
                $request->update(['status' => 'bidding']);
            }

            return $proposal->load(['items', 'provider']);
        });
    }

    /**
     * Update an existing pending proposal.
     *
     * @throws DomainException
     */
    public function updateProposal(Proposal $proposal, array $data): Proposal
    {
        if ($proposal->status !== 'pending') {
            throw new DomainException(__('Cannot modify proposal because it is already :status.', [
                'status' => $proposal->status,
            ]));
        }

        return DB::transaction(function () use ($proposal, $data) {
            if (isset($data['message'])) {
                $proposal->message = $data['message'];
            }
            if (isset($data['valid_until'])) {
                $proposal->valid_until = $data['valid_until'];
            }

            if (isset($data['items']) && is_array($data['items'])) {
                $proposal->items()->delete();

                $computedTotalPrice = 0.00;
                foreach ($data['items'] as $item) {
                    $computedTotalPrice += (float) ($item['price'] ?? 0.00);
                    ProposalItem::create([
                        'proposal_id' => $proposal->id,
                        'service_id' => $item['service_id'] ?? null,
                        'name' => $item['name'],
                        'price' => (float) ($item['price'] ?? 0.00),
                        'specs' => $item['specs'] ?? null,
                        'is_accepted' => false,
                    ]);
                }

                $proposal->total_price = isset($data['total_price']) && (float) $data['total_price'] > 0
                    ? (float) $data['total_price']
                    : $computedTotalPrice;
            } elseif (isset($data['total_price'])) {
                $proposal->total_price = (float) $data['total_price'];
            }

            $proposal->save();

            return $proposal->load(['items', 'provider']);
        });
    }

    /**
     * Build a side-by-side comparison matrix for all proposals of a travel request.
     *
     * @return array{
     *     request: array,
     *     stats: array,
     *     proposals: array
     * }
     */
    public function getComparisonMatrix(TravelRequest $request): array
    {
        $proposals = $request->proposals()
            ->with(['provider.user', 'provider.destination', 'items', 'insight'])
            ->get();

        $prices = $proposals->pluck('total_price')->map(fn ($p) => (float) $p)->filter(fn ($p) => $p > 0);
        $minPrice = $prices->min() ?? 0.0;
        $maxPrice = $prices->max() ?? 0.0;
        $avgPrice = $prices->avg() ? round($prices->avg(), 2) : 0.0;

        $matrixProposals = $proposals->map(function (Proposal $proposal) use ($minPrice, $request) {
            $totalPrice = (float) $proposal->total_price;
            $maxBudget = (float) $request->max_budget;
            $savings = $maxBudget > 0 ? max(0, round($maxBudget - $totalPrice, 2)) : 0.0;

            return [
                'id' => $proposal->id,
                'total_price' => $totalPrice,
                'message' => $proposal->message,
                'valid_until' => $proposal->valid_until,
                'status' => $proposal->status,
                'is_lowest_price' => $totalPrice > 0 && $totalPrice <= $minPrice,
                'savings' => $savings,
                'insight' => $proposal->insight ? [
                    'score' => $proposal->insight->score,
                    'badge' => $proposal->insight->badge,
                    'badge_label' => $proposal->insight->badge_label,
                    'badge_classes' => $proposal->insight->badge_classes,
                    'pros' => $proposal->insight->pros ?? [],
                    'cons' => $proposal->insight->cons ?? [],
                    'hidden_costs' => $proposal->insight->hidden_costs ?? [],
                    'summary_verdict' => $proposal->insight->summary_verdict,
                ] : null,
                'provider' => [
                    'id' => $proposal->provider?->id,
                    'user_id' => $proposal->provider?->user_id,
                    'username' => $proposal->provider?->user?->username,
                    'business_name' => $proposal->provider?->business_name ?? __('Local Provider'),
                    'service_type' => $proposal->provider?->service_type,
                    'rating' => (float) ($proposal->provider?->rating ?? 5.0),
                    'phone' => $proposal->provider?->phone,
                    'address' => $proposal->provider?->address,
                    'is_verified' => (bool) ($proposal->provider?->is_verified ?? false),
                ],
                'items' => $proposal->items->map(fn (ProposalItem $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => (float) $item->price,
                    'specs' => $item->specs,
                    'is_accepted' => (bool) $item->is_accepted,
                ])->all(),
            ];
        })->all();

        return [
            'request' => [
                'id' => $request->id,
                'trip_id' => $request->trip_id,
                'trip_name' => $request->trip?->name,
                'destination' => $request->trip?->destination,
                'needs' => $request->needs,
                'pace' => $request->pace,
                'max_budget' => (float) $request->max_budget,
                'status' => $request->status,
            ],
            'stats' => [
                'count' => $proposals->count(),
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
                'avg_price' => $avgPrice,
            ],
            'proposals' => $matrixProposals,
        ];
    }
}
