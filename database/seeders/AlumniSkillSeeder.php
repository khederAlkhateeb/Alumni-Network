<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\Skill;
use App\Models\AlumniSkill;
use Illuminate\Database\Seeder;

class AlumniSkillSeeder extends Seeder
{
    public function run(): void
    {
        $skillIds = Skill::pluck('id');

     AlumniProfile::all()->each(function (AlumniProfile $alumniProfile) use ($skillIds) {
            $randomSkillIds = $skillIds->random(min(rand(2, 5), $skillIds->count()));

            foreach ((array) $randomSkillIds as $skillId) {
                Skill::factory()->create([
                    'alumni_profile_id' => $alumniProfile->id,
                    'skill_id' => $skillId,
                ]);
            }
        });
    }
}
