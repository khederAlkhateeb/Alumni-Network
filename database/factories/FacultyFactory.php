<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacultyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'name' => $this->faker->randomElement([
                'Faculty of Engineering',
                'Faculty of Business',
                'Faculty of Computer Science',
                'Faculty of Medicine',
                'Faculty of Arts and Humanities',
                'Faculty of Law',
                'Faculty of Science',
            ]),
        ];
    }
}
