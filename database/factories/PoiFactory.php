<?php

namespace Database\Factories;

use App\Models\Poi;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Poi>
 */
class PoiFactory extends Factory
{
    protected $model = Poi::class;

    public function definition(): array
    {
        return [
            'zone_id' => Zone::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Cafe', 'Restaurant', 'Square', 'Waterfall', 'Garden']),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'category' => fake()->randomElement(['Sightseeing', 'Dining', 'Cafe']),
            'typical_cost' => fake()->randomFloat(2, 0, 500000),
        ];
    }
}
