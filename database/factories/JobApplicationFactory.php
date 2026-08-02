<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: JobApplicationFactory
 *
 * Generates realistic job application records for testing and seeding.
 *
 * Purpose:
 * - Simulates job applications submitted by users for job listings.
 * - Supports multiple application statuses such as submitted, reviewed,
 *   shortlisted, and rejected.
 *
 * Behavior:
 * - Automatically creates a JobListing using its factory.
 * - Automatically creates an applicant User using its factory.
 * - Generates a cover letter with two paragraphs.
 * - Creates a fake resume file path using a UUID.
 * - Randomly assigns an application status.
 *
 * Usage:
 * - JobApplication::factory()->create();
 * - JobApplication::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\JobApplication
 * - App\Models\JobListing
 * - App\Models\User
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'job_listing_id' => JobListing::factory(),
            'applicant_id'   => User::factory(),

            // Two-paragraph cover letter
            'cover_letter'   => $this->faker->paragraphs(2, true),

            // Fake resume file path
            'resume'         => 'resumes/' . $this->faker->uuid() . '.pdf',

            // Application status
            'status'         => $this->faker->randomElement([
                'submitted',
                'reviewed',
                'shortlisted',
                'rejected',
            ]),

            // Optional created_at field (commented out)
            // 'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
