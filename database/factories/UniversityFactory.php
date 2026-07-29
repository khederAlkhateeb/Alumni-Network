<?php

namespace Database\Factories;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<University>
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
        return [
            'name' => fake()->name(),
            'country' => fake()->country(),
            'website' => fake()->url(),
            'logo' => fake()->url(),
            'created_by' => User::factory()->create()->id,
        ];
    }
}
