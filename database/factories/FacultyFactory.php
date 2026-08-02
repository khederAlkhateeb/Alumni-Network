<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: FacultyFactory
 *
 * Generates realistic faculty records for testing and database seeding.
 *
 * Purpose:
 * - Simulates university faculties such as Engineering, Business, Medicine, etc.
 * - Ensures each faculty is associated with a valid university.
 *
 * Behavior:
 * - Automatically creates a University using its factory.
 * - Randomly selects a faculty name from a predefined list.
 *
 * Usage:
 * - Faculty::factory()->create();
 * - Faculty::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\Faculty
 * - App\Models\University
 */
class FacultyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
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
