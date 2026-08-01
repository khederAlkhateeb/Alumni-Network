<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: CommentFactory
 *
 * Generates fake comment records for testing and seeding.
 *
 * Purpose:
 * - Creates realistic comment entries associated with posts and users.
 * - Supports nested comments (replies) through the `parent_comment_id` field.
 *
 * Behavior:
 * - Automatically creates a Post using its factory.
 * - Automatically creates a User using its factory.
 * - Generates random comment content.
 * - Sets a realistic created_at timestamp within the last year.
 *
 * Usage:
 * - Comment::factory()->create();
 * - Comment::factory()->count(10)->create();
 * - Comment::factory()->reply($parentId, $postId)->create();
 *
 * Related Models:
 * - App\Models\Post
 * - App\Models\User
 * - App\Models\Comment (polymorphic or hierarchical comment structure)
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'post_id'            => Post::factory(),
            'user_id'            => User::factory(),
            'parent_comment_id'  => null,
            'content'            => $this->faker->sentence(12),
            'created_at'         => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Define a reply (nested comment) state.
     *
     * Behavior:
     * - Sets the parent_comment_id to the provided parent comment.
     * - Ensures the reply belongs to the same post.
     *
     * @param int $parentCommentId The ID of the parent comment.
     * @param int $postId          The ID of the post the reply belongs to.
     *
     * @return static
     */
    public function reply(int $parentCommentId, int $postId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_comment_id' => $parentCommentId,
            'post_id'           => $postId,
        ]);
    }
}
