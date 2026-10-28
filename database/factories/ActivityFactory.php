<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['sightseeing', 'dining', 'cafe', 'shopping', 'entertainment', 'other'];
        $slots = ['morning', 'midday', 'afternoon', 'evening'];
        $slot = fake()->randomElement($slots);
        $slotTimes = match ($slot) {
            'morning' => ['08:00', '10:00'],
            'midday' => ['11:30', '13:00'],
            'afternoon' => ['14:30', '16:00'],
            'evening' => ['18:30', '20:30'],
        };

        return [
            'trip_id' => Trip::factory(),
            'day' => fake()->numberBetween(1, 3),
            'time_slot' => $slot,
            'start_time' => $slotTimes[0],
            'end_time' => $slotTimes[1],
            'title' => fake()->sentence(3),
            'cost' => fake()->randomFloat(2, 50000, 500000),
            'category' => fake()->randomElement($categories),
            'location' => fake()->company(),
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'type' => 'activity',
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
