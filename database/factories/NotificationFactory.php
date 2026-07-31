<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\JobListing;
use App\Models\MentorshipRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $isRead = $this->faker->boolean(50);

        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement([
                'connection_request', 'job_application', 'event_reminder',
                'mentorship_request', 'new_message', 'post_reaction', 'new_comment',
            ]),
                  'related_id' => $this->faker->numberBetween(1, 50),
            'related_type' => $this->faker->randomElement([
               Connection::class,
                Post::class,
               JobListing::class,
             MentorshipRequest::class,
            ]),
            'message' => $this->faker->sentence(10),
            'read_at' => $isRead ? $this->faker->dateTimeBetween($createdAt, 'now') : null,
            'created_at' => $createdAt,
        ];
    }
}
