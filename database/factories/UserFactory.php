<?php

namespace Database\Factories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * --------------------------------------------------------------------------
 * UserFactory
 * --------------------------------------------------------------------------
 *
 * Factory responsible for generating realistic fake data for the User model.
 * Used in:
 * - Database seeding
 * - Automated tests
 * - Local development environments
 *
 * Fields:
 * - name: Random full name
 * - email: Unique, safe email address
 * - password: Hashed default password ("password")
 * - is_active: Boolean indicating whether the user account is active
 *
 * Notes:
 * - `password` uses Hash::make() to ensure proper hashing for tests.
 * - `is_active` has a 10% chance of being true, simulating inactive accounts.
 *
 * Additional States:
 * - inactive(): Forces the user to be inactive.
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'is_active' => $this->faker->boolean(10),
        ];
    }

    /**
     * Mark the user as inactive.
     *
     * @return static
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
