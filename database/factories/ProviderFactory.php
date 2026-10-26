<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'provider']),
            'business_name' => fake()->company().' Travel Services',
            'service_type' => fake()->randomElement(['transport', 'tour', 'accommodation', 'restaurant', 'photo', 'guide']),
            'destination_id' => Destination::first()?->id ?? Destination::factory(),
            'bio' => fake()->paragraph(),
            'rating' => fake()->randomFloat(1, 4, 5),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_verified' => true,
        ];
    }
}
