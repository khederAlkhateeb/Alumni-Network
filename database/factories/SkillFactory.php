<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * --------------------------------------------------------------------------
 * SkillFactory
 * --------------------------------------------------------------------------
 *
 * Factory responsible for generating realistic fake data for the Skill model.
 * Used in:
 * - Database seeding
 * - Automated tests
 * - Local development environments
 *
 * Fields:
 * - name: Random two‑word skill name (capitalized)
 * - category: One of the predefined skill categories
 *
 * Categories include:
 * - Backend
 * - Frontend
 * - Design
 * - Management
 * - DevOps
 * - Data
 *
 * Notes:
 * - `name` uses Faker's unique() to avoid duplicates.
 * - `category` is randomly selected from a fixed list.
 */
class SkillFactory extends Factory
{
    /**
     * The associated model for this factory.
     *
     * @var class-string<Skill>
     */
    protected $model = Skill::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'category' => $this->faker->randomElement([
                'Backend', 'Frontend', 'Design', 'Management', 'DevOps', 'Data',
            ]),
        ];
    }
}
