<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

class MajorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'name' => $this->faker->randomElement([
                'Software Engineering',
                'Civil Engineering',
                'Marketing',
                'Finance',
                'Data Science',
                'Mechanical Engineering',
                'Graphic Design',
                'Law',
                'Nursing',
            ]),
        ];
    }
}
