<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: AlumniSkillFactory
 *
 * Generates fake data for the pivot table linking alumni profiles
 * with their associated skills.
 *
 * Purpose:
 * - Used in testing and seeding to create realistic many‑to‑many
 *   relationships between AlumniProfile and Skill models.
 *
 * Behavior:
 * - Automatically creates an AlumniProfile using its factory.
 * - Automatically creates a Skill using its factory.
 *
 * Usage:
 * - AlumniSkill::factory()->create();
 * - AlumniSkill::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\AlumniProfile
 * - App\Models\Skill
 */
class AlumniSkillFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'alumni_profile_id' => AlumniProfile::factory(),
            'skill_id'          => Skill::factory(),
        ];
    }
}
