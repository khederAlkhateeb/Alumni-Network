<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: ConversationFactory
 *
 * Generates fake conversation records for testing and seeding.
 *
 * Purpose:
 * - Creates realistic conversation entries for messaging or chat features.
 * - Typically used to simulate chat threads between users.
 *
 * Behavior:
 * - Generates a realistic created_at timestamp within the last year.
 * - Does not assign participants directly; they are usually created
 *   through pivot tables or related factories (e.g., ConversationUserFactory).
 *
 * Usage:
 * - Conversation::factory()->create();
 * - Conversation::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\Conversation
 * - App\Models\User (via conversation participants)
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
