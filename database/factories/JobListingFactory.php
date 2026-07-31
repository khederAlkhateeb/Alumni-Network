<?php

namespace Database\Factories;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobListingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'posted_by_user_id' => User::factory(),
            'title' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'location' => $this->faker->city(),
            'type' => $this->faker->randomElement(['full_time', 'part_time', 'internship', 'remote']),
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => $this->faker->paragraphs(2, true),
            'salary_range' => $this->faker->randomElement(['800-1200 JOD', '1200-2000 JOD', '2000-3500 JOD', 'Negotiable']),
            'expires_at' => $this->faker->dateTimeBetween('now', '+3 months'),
            'status' => $this->faker->randomElement(['active', 'closed', 'expired']),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
