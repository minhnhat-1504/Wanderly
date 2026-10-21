<?php

namespace Database\Factories;

use App\Models\RoomBooking;
use App\Models\Trip;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomBooking>
 */
class RoomBookingFactory extends Factory
{
    protected $model = RoomBooking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $checkIn = Carbon::now()->addDays(fake()->numberBetween(1, 10));
        $checkOut = (clone $checkIn)->addDays(fake()->numberBetween(1, 4));

        return [
            'trip_id' => Trip::factory(),
            'hotel_name' => fake()->company().' Hotel & Suites',
            'room_type' => fake()->randomElement(['Standard Room', 'Deluxe Double', 'Superior King', 'Family Suite']),
            'check_in' => $checkIn->format('Y-m-d'),
            'check_out' => $checkOut->format('Y-m-d'),
            'price' => fake()->randomFloat(2, 500000, 4000000),
            'address' => fake()->address(),
            'notes' => fake()->sentence(),
            'is_budget_included' => true,
        ];
    }
}
