<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Seeder;

class WorkExperienceSeeder extends Seeder
{
    public function run(): void
    {
            AlumniProfile::all()->each(function (AlumniProfile $alumniProfile) {
            WorkExperience::factory()
                ->count(rand(1, 4))
                ->create(['alumni_profile_id' => $alumniProfile->id]);
        });
    }
}
