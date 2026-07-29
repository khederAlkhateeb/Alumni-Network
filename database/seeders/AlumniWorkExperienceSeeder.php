<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Seeder;

class AlumniWorkExperienceSeeder extends Seeder
{
    public function run(): void
    {
        $profile = AlumniProfile::first();

        if (! $profile) {
            $this->command->warn('No alumni profile found. Run AlumniProfileSeeder first.');
            return;
        }


        WorkExperience::factory()
            ->count(2)
            ->past()
            ->create(['alumni_profile_id' => $profile->id]);

        WorkExperience::factory()
            ->current()
            ->create(['alumni_profile_id' => $profile->id]);
    }
}
