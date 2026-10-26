<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'name' => fake()->randomElement([
                'Private 7-Seater Airport Transfer',
                'Dalat Waterfall & Countryside Tour',
                'Deluxe King Garden View Room',
                'Sunrise Cloud Hunting & Coffee Tour',
            ]),
            'price' => fake()->randomFloat(2, 500000, 3000000),
            'tags' => ['tour', 'transport', 'sightseeing'],
            'specs' => ['seats' => 7, 'ac' => true, 'duration' => 'full-day'],
        ];
    }
}
