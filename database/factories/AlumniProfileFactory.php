<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'major_id' => Major::factory(),
            'student_number' => 'STU-' . $this->faker->unique()->numerify('######'),
            'graduation_year' => $this->faker->numberBetween(2010, 2025),
            'current_job_title' => $this->faker->jobTitle(),
            'current_company' => $this->faker->company(),
            'linkedin_url' => 'https://linkedin.com/in/' . $this->faker->userName(),
            'bio' => $this->faker->paragraph(),
            'city' => $this->faker->city(),
            'country' => $this->faker->country(),
            'status' => $this->faker->randomElement(['pending', 'active', 'suspended']),
            'is_open_to_mentor' => $this->faker->boolean(30),
            'created_at' => now(),
        ];
    }

    public function mentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open_to_mentor' => true,
            'status' => 'active',
        ]);
    }
}
