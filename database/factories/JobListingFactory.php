<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: JobListingFactory
 *
 * Generates realistic job listing records for testing and database seeding.
 *
 * Purpose:
 * - Simulates job postings created by university career centers or admins.
 * - Supports multiple job types (full-time, part-time, internship, remote).
 * - Includes realistic company, location, salary, and expiration data.
 *
 * Behavior:
 * - Automatically creates a University using its factory.
 * - Automatically creates a User as the job poster.
 * - Generates job details such as title, company, description, requirements.
 * - Randomly assigns a salary range and job status.
 * - Ensures expiration dates fall within the next 3 months.
 * - Adds a realistic created_at timestamp within the last 6 months.
 *
 * Usage:
 * - JobListing::factory()->create();
 * - JobListing::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\JobListing
 * - App\Models\University
 * - App\Models\User
 */
class JobListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'university_id'       => University::factory(),
            'posted_by_user_id'   => User::factory(),

            'title'               => $this->faker->jobTitle(),
            'company'             => $this->faker->company(),
            'location'            => $this->faker->city(),

            'type'                => $this->faker->randomElement([
                'full_time',
                'part_time',
                'internship',
                'remote',
            ]),

            'description'         => $this->faker->paragraphs(3, true),
            'requirements'        => $this->faker->paragraphs(2, true),

            'salary_range'        => $this->faker->randomElement([
                '800-1200 JOD',
                '1200-2000 JOD',
                '2000-3500 JOD',
                'Negotiable',
            ]),

            'expires_at'          => $this->faker->dateTimeBetween('now', '+3 months'),

            'status'              => $this->faker->randomElement([
                'active',
                'closed',
                'expired',
            ]),

            'created_at'          => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
