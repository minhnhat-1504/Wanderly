<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'trip_id' => null,
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraphs(3, true),
            'images' => null,
            'likes_count' => 0,
            'comments_count' => 0,
        ];
    }
}
