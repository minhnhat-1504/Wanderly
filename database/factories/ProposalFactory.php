<?php

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\Provider;
use App\Models\TravelRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
class ProposalFactory extends Factory
{
    protected $model = Proposal::class;

    public function definition(): array
    {
        return [
            'request_id' => TravelRequest::factory(),
            'provider_id' => Provider::factory(),
            'total_price' => fake()->randomFloat(2, 1000000, 5000000),
            'message' => fake()->paragraph(),
            'valid_until' => now()->addDays(7),
            'status' => 'pending',
        ];
    }
}
