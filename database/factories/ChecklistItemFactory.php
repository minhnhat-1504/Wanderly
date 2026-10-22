<?php

namespace Database\Factories;

use App\Models\ChecklistItem;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    protected $model = ChecklistItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'title' => fake()->randomElement([
                'Warm Jacket',
                'Camera & Extra Battery',
                'ID Card & Passport Copies',
                'Sneakers for Walking',
                'Sunscreen SPF 50+',
                'Water Bottle',
                'First Aid Kit',
                'Insect Repellent',
                'Power Bank',
            ]),
            'category' => fake()->randomElement(['clothing', 'gear', 'electronics', 'health', 'documents']),
            'is_completed' => fake()->boolean(40),
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
