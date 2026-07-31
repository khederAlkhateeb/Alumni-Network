<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

class MentorshipProgramFactory extends Factory
{
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
            'end_date' => (clone $start)->modify('+3 months'),
            'mentor_per_mentees_max' => $this->faker->numberBetween(1, 5),
          'status' => fake()->randomElement(['draft', 'active', 'closed']),

        ];
    }
}
