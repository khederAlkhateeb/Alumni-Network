<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: MentorshipProgramFactory
 *
 * Generates realistic mentorship program records for testing and seeding.
 *
 * Purpose:
 * - Simulates mentorship programs created by universities.
 * - Supports multiple program types such as seasonal cohorts and career-focused mentorship tracks.
 *
 * Behavior:
 * - Automatically creates a University using its factory.
 * - Randomly selects a program title from a predefined list.
 * - Generates a start date within a realistic timeframe (past 2 months to next month).
 * - Ensures end_date is always 3 months after the start date.
 * - Randomly assigns a maximum number of mentees per mentor.
 * - Randomly assigns a program status (draft, active, closed).
 *
 * Usage:
 * - MentorshipProgram::factory()->create();
 * - MentorshipProgram::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\MentorshipProgram
 * - App\Models\University
 */
class MentorshipProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-2 months', '+1 month');

        return [
            'university_id' => University::factory(),

            'title' => $this->faker->randomElement([
                'Spring Mentorship Cohort',
                'Career Kickstart Program',
                'Tech Leaders Mentorship',
                'Fall Mentorship Circle',
            ]),

            'start_date' => $start,
            'end_date'   => (clone $start)->modify('+3 months'),

            'mentor_per_mentees_max' => $this->faker->numberBetween(1, 5),

            'status' => fake()->randomElement(['draft', 'active', 'closed']),
        ];
    }
}
