<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Proposal;
use App\Models\Provider;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'provider_id' => Provider::factory(),
            'proposal_id' => Proposal::factory(),
            'item_name' => fake()->randomElement(['Private Airport Shuttle', 'Dalat Countryside Tour']),
            'price' => fake()->randomFloat(2, 500000, 2500000),
            'status' => 'confirmed',
            'voucher_code' => 'WND-DEMO-'.strtoupper(fake()->bothify('##??')),
            'notes' => fake()->sentence(),
        ];
    }
}
