<?php

namespace Database\Factories;

use App\Models\MentorshipProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorshipRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'program_id' => MentorshipProgram::factory(),
            'mentor_id' => User::factory(),
            'mentee_id' => User::factory(),
            'intro_message' => $this->faker->paragraph(),
'status' => fake()->randomElement(['pending', 'accepted', 'rejected', 'complete']),
            // 'created_at' => $this->faker->dateTimeBetween('-2 months', 'now'),
        ];
    }
}
