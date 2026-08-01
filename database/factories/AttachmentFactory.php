<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: AttachmentFactory
 *
 * Generates fake attachment records for testing and seeding.
 *
 * Purpose:
 * - Creates realistic attachment entries for models using polymorphic
 *   relationships (attachable_id + attachable_type).
 * - Commonly used for attaching files to posts, comments, or other entities.
 *
 * Behavior:
 * - Creates a Post model automatically using its factory.
 * - Sets attachable_type to Post::class (polymorphic relation).
 * - Generates a random file path with a UUID and random extension.
 * - Assigns a realistic created_at timestamp within the last year.
 *
 * Usage:
 * - Attachment::factory()->create();
 * - Attachment::factory()->count(5)->create();
 *
 * Related Models:
 * - App\Models\Post
 * - Any model using a polymorphic "attachable" relation.
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'attachable_id'   => Post::factory(),
            'attachable_type' => Post::class,
            'file_path'       => 'attachments/' . $this->faker->uuid() . '.' .
                                 $this->faker->randomElement(['jpg', 'png', 'pdf', 'docx']),
            'created_at'      => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
