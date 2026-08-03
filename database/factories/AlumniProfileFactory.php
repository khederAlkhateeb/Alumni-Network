<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * --------------------------------------------------------------------------
 * AlumniProfileFactory
 * --------------------------------------------------------------------------
 *
 * Factory responsible for generating realistic fake data for the
 * AlumniProfile model. Used primarily in:
 * - Database seeding
 * - Automated tests
 * - Local development environments
 *
 * Relationships:
 * - user_id → User::factory()
 * - major_id → Major::factory()
 *
 * Fields:
 * - student_number: Unique student identifier (STU-######)
 * - graduation_year: Random year between 2010–2025
 * - current_job_title: Fake job title
 * - current_company: Fake company name
 * - linkedin_url: Fake LinkedIn profile URL
 * - bio: Short paragraph describing the alumni
 * - city / country: Geographic info
 * - status: One of: pending, active, suspended
 * - is_open_to_mentor: Boolean (30% chance of true)
 *
 * Additional States:
 * - mentor(): Marks the alumni as an active mentor
 */
class AlumniProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Mark the alumni profile as an active mentor.
     *
     * @return static
     */
    public function mentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open_to_mentor' => true,
            'status' => 'active',
        ]);
    }
}
