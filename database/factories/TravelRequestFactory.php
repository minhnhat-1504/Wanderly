<?php

namespace Database\Factories;

use App\Models\TravelRequest;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelRequest>
 */
class TravelRequestFactory extends Factory
{
    protected $model = TravelRequest::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'needs' => ['transport', 'tour'],
            'pace' => 'balanced',
            'max_budget' => fake()->randomFloat(2, 2000000, 10000000),
            'status' => 'open',
            'notes' => fake()->sentence(),
        ];
    }
}
