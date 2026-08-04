<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * --------------------------------------------------------------------------
 * UniversityFactory
 * --------------------------------------------------------------------------
 *
 * Factory responsible for generating realistic fake data for the University
 * model. Used in:
 * - Database seeding
 * - Automated tests
 * - Local development environments
 *
 * Fields:
 * - name: Random university name (e.g., "Acme University")
 * - country: Random country name
 * - website: Auto‑generated .edu domain based on the university name
 * - logo: Nullable (can be filled later by tests or seeders)
 *
 * Notes:
 * - `name` uses Faker's company() to simulate real university naming patterns.
 * - `website` uses Laravel's `str()->slug()` helper to generate a clean domain.
 * - `logo` is intentionally null to allow flexible testing scenarios.
 */
class UniversityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company() . ' University';

        return [
            'name' => $name,
            'country' => $this->faker->country(),
            'website' => 'https://' . str($name)->slug() . '.edu',
            'logo' => null,
            // 'created_at' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
        ];
    }
}
