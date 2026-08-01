<?php

namespace Database\Factories;

use App\Models\MentorshipProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: MentorshipRequestFactory
 *
 * Generates realistic mentorship request records for testing and seeding.
 *
 * Purpose:
 * - Simulates mentorship requests submitted by mentees to mentors within a program.
 * - Supports multiple request statuses such as pending, accepted, rejected, and complete.
 *
 * Behavior:
 * - Automatically creates a MentorshipProgram using its factory.
 * - Automatically creates both mentor and mentee users using their factories.
 * - Generates an introductory message from the mentee.
 * - Randomly assigns a request status.
 *
 * Usage:
 * - MentorshipRequest::factory()->create();
 * - MentorshipRequest::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\MentorshipRequest
 * - App\Models\MentorshipProgram
 * - App\Models\User (mentor & mentee)
 */
class MentorshipRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'program_id'    => MentorshipProgram::factory(),
            'mentor_id'     => User::factory(),
            'mentee_id'     => User::factory(),

            // Introductory message from the mentee
            'intro_message' => $this->faker->paragraph(),

            // Request status
            'status'        => fake()->randomElement([
                'pending',
                'accepted',
                'rejected',
                'complete',
            ]),

            // Optional created_at field (commented out)
            // 'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
