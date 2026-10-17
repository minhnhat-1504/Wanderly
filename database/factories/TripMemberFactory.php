<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripMember>
 */
class TripMemberFactory extends Factory
{
    protected $model = TripMember::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['leader', 'editor', 'viewer']),
        ];
    }
}
