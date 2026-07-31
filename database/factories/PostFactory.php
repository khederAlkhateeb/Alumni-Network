<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: PostFactory
 *
 * Generates realistic post records for testing and seeding.
 *
 * Purpose:
 * - Simulates user-generated posts within the platform.
 * - Supports multiple visibility levels such as public, connections-only,
 *   or university-wide visibility.
 *
 * Behavior:
 * - Automatically creates a User using its factory.
 * - Generates realistic text content up to ~200 characters.
 * - Randomly assigns a visibility level.
 * - Generates a created_at timestamp within the last year.
 *
 * Usage:
 * - Post::factory()->create();
 * - Post::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\Post
 * - App\Models\User
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),

            // Post content (approx. 200 characters)
            'content'    => $this->faker->realText(200),

            // Visibility options
            'visibility' => $this->faker->randomElement([
                'public',
                'connections',
                'university',
            ]),

            // Creation timestamp
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
