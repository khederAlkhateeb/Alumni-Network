<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_listing_id' => JobListing::factory(),
            'applicant_id' => User::factory(),
            'cover_letter' => $this->faker->paragraphs(2, true),
            'resume' => 'resumes/' . $this->faker->uuid() . '.pdf',
            'status' => $this->faker->randomElement(['submitted', 'reviewed', 'shortlisted', 'rejected']),
            // 'created_at' => $this->faker->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
