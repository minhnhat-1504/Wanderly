<?php

namespace Database\Factories;

use App\Models\Destination;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'destination_id' => Destination::factory(),
            'name' => fake()->streetName().' Area',
        ];
    }
}
