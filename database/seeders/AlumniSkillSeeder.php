<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class AlumniSkillSeeder extends Seeder
{
    public function run(): void
    {

        $skills = Skill::factory()->count(15)->create();

        $profile = AlumniProfile::first();

        if (! $profile) {
            $this->command->warn('No alumni profile found. Run AlumniProfileSeeder first.');
            return;
        }

        $profile->skills()->syncWithoutDetaching(
            $skills->random(min(4, $skills->count()))->pluck('id')
        );
    }
}
