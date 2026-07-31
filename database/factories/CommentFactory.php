<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'parent_comment_id' => null,
            'content' => $this->faker->sentence(12),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }


    public function reply(int $parentCommentId, int $postId): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_comment_id' => $parentCommentId,
            'post_id' => $postId,
        ]);
    }
}
