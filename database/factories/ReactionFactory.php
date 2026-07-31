<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: ReactionFactory
 *
 * Generates realistic reaction records for testing and seeding.
 *
 * Purpose:
 * - Simulates user reactions on various reactable entities (posts, comments, etc.).
 * - Supports polymorphic relationships through reactable_id and reactable_type.
 *
 * Behavior:
 * - Generates a reaction type such as like, insightful, or celebrate.
 * - Leaves reactable_id, reactable_type, and user_id as null by default,
 *   allowing test cases to explicitly assign them.
 *
 * Usage:
 * - Reaction::factory()->create();
 * - Reaction::factory()->state(['reactable_id' => $post->id, 'reactable_type' => Post::class])->create();
 * - Reaction::factory()->state(['user_id' => $user->id])->create();
 *
 * Related Models:
 * - App\Models\Reaction
 * - App\Models\User
 * - Any reactable model (Post, Comment, etc.)
 */
class ReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition()
    {
        return [
            // Reaction type
            'type' => $this->faker->randomElement([
                'like',
                'insightful',
                'celebrate',
            ]),

            // Polymorphic relation fields (assigned manually in tests)
            'reactable_id'   => null,
            'reactable_type' => null,

            // User performing the reaction (assigned manually)
            'user_id'        => null,
        ];
    }
}
