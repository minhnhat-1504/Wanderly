<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'booking_id' => null,
            'sender_id' => User::factory(),
            'receiver_id' => null,
            'content' => fake()->sentence(),
            'attachment' => null,
            'is_read' => false,
        ];
    }
}
