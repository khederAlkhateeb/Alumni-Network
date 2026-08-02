<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: MajorFactory
 *
 * Generates realistic academic major records for testing and database seeding.
 *
 * Purpose:
 * - Simulates university majors belonging to specific faculties.
 * - Ensures each major is associated with a valid faculty.
 *
 * Behavior:
 * - Automatically creates a Faculty using its factory.
 * - Randomly selects a major name from a predefined list of common academic majors.
 *
 * Usage:
 * - Major::factory()->create();
 * - Major::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\Major
 * - App\Models\Faculty
 */
class MajorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
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
