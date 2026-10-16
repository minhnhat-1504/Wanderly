<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    protected $model = Trip::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = Carbon::now()->addDays(fake()->numberBetween(1, 30))->startOfDay();
        $endDate = (clone $startDate)->addDays(fake()->numberBetween(3, 5));

        return [
            'owner_id' => User::factory(),
            'name' => fake()->randomElement(['Trip to ', 'Holiday in ', 'Exploring ']).fake()->city(),
            'destination' => fake()->randomElement(['Da Lat', 'Da Nang', 'Phu Quoc', 'Nha Trang', 'Hoi An', 'Sa Pa']),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'budget' => fake()->randomFloat(2, 2000000, 15000000),
            'cover' => null,
            'privacy' => fake()->randomElement(['private', 'group', 'public']),
            'status' => 'planning',
        ];
    }
}
