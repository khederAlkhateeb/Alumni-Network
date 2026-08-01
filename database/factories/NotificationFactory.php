<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\JobListing;
use App\Models\MentorshipRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: NotificationFactory
 *
 * Generates realistic notification records for testing and seeding.
 *
 * Purpose:
 * - Simulates user notifications triggered by various system events:
 *   connection requests, job applications, mentorship requests,
 *   post interactions, messages, and event reminders.
 *
 * Behavior:
 * - Automatically creates a User using its factory.
 * - Randomly selects a notification type from a predefined list.
 * - Generates a polymorphic relation (related_id + related_type)
 *   pointing to one of several possible models.
 * - Creates a readable message summarizing the notification.
 * - Randomly determines whether the notification has been read.
 * - Ensures read_at occurs after created_at when applicable.
 *
 * Usage:
 * - Notification::factory()->create();
 * - Notification::factory()->count(20)->create();
 *
 * Related Models:
 * - App\Models\Notification
 * - App\Models\User
 * - App\Models\Connection
 * - App\Models\Post
 * - App\Models\JobListing
 * - App\Models\MentorshipRequest
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        $createdAt = $this->faker->dateTimeBetween('-3 months', 'now');
        $isRead    = $this->faker->boolean(50);

        return [
            'user_id' => User::factory(),

            // Notification category/type
            'type' => $this->faker->randomElement([
                'connection_request',
                'job_application',
                'event_reminder',
                'mentorship_request',
                'new_message',
                'post_reaction',
                'new_comment',
            ]),

            // Polymorphic relation target
            'related_id' => $this->faker->numberBetween(1, 50),
            'related_type' => $this->faker->randomElement([
                Connection::class,
                Post::class,
                JobListing::class,
                MentorshipRequest::class,
            ]),

            // Notification message
            'message' => $this->faker->sentence(10),

            // Read timestamp (optional)
            'read_at' => $isRead
                ? $this->faker->dateTimeBetween($createdAt, 'now')
                : null,

            'created_at' => $createdAt,
        ];
    }
}
