<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniSkillFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alumni_profile_id' => AlumniProfile::factory(),
            'skill_id' => Skill::factory(),
        ];
    }
}
