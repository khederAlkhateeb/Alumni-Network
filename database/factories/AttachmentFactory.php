<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [

            'attachable_id' => Post::factory(),
            'attachable_type' => Post::class,
            'file_path' => 'attachments/' . $this->faker->uuid() . '.' . $this->faker->randomElement(['jpg', 'png', 'pdf', 'docx']),
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
