<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\ProposalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposalItem>
 */
class ProposalItemFactory extends Factory
{
    protected $model = ProposalItem::class;

    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'service_id' => null,
            'name' => fake()->randomElement(['Private Car Transfer', 'Coffee Tasting Tour', 'Sunrise Cloud Trek']),
            'price' => fake()->randomFloat(2, 400000, 2000000),
            'specs' => ['duration' => 'half-day'],
            'is_accepted' => false,
        ];
    }
}
