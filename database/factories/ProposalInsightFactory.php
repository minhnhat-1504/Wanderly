<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalInsight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalInsight>
 */
class ProposalInsightFactory extends Factory
{
    protected $model = ProposalInsight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'pros' => [
                'Comfortable private vehicle with air conditioning',
                'English-speaking certified local guide included',
                'Complimentary mineral water and cold towels',
            ],
            'cons' => [
                'Requires 48-hour advance notice for rescheduling',
            ],
            'hidden_costs' => [
                'Entrance tickets to tourist attractions (~120,000 VND / person)',
                'Driver tip and lunch are not included',
            ],
            'summary_verdict' => 'A well-balanced package offering great comfort and responsive local support.',
            'score' => 88,
            'badge' => ProposalInsight::BADGE_BEST_VALUE,
            'suggested_combos' => null,
        ];
    }
}
