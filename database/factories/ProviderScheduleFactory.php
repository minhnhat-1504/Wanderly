<?php

namespace Database\Factories;

use App\Models\Provider;
use App\Models\ProviderSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderSchedule>
 */
class ProviderScheduleFactory extends Factory
{
    protected $model = ProviderSchedule::class;

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'blocked_date' => now()->addDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
            'reason' => fake()->randomElement(['Fully Booked', 'Maintenance', 'Personal Leave']),
        ];
    }
}
