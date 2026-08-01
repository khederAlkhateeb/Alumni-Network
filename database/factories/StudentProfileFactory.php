<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: StudentProfileFactory
 *
 * Generates realistic student profile records for testing and seeding.
 *
 * Purpose:
 * - Simulates academic student profiles linked to users and majors.
 * - Supports enrollment details, academic status, and graduation expectations.
 *
 * Behavior:
 * - Automatically creates a User using its factory.
 * - Automatically creates a Major using its factory.
 * - Generates a unique enrollment number (6 digits).
 * - Randomly selects an enrollment year between 2019 and 2025.
 * - Calculates expected graduation year as enrollment_year + 4.
 * - Randomly assigns a student status (pending, active, suspended).
 * - Sets created_at to the current timestamp.
 *
 * Usage:
 * - StudentProfile::factory()->create();
 * - StudentProfile::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\StudentProfile
 * - App\Models\User
 * - App\Models\Major
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        $enrollmentYear = $this->faker->numberBetween(2019, 2025);

        return [
            'user_id'                  => User::factory(),
            'major_id'                 => Major::factory(),

            // Unique student enrollment number
            'enrollment_number'        => $this->faker->unique()->numerify('######'),

            'enrollment_year'          => $enrollmentYear,
            'expected_graduation_year' => $enrollmentYear + 4,

            // Student academic status
            'status'                   => $this->faker->randomElement([
                'pending',
                'active',
                'suspended',
            ]),

            'created_at'               => now(),
        ];
    }
}
