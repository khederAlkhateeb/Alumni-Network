<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\MentorshipProgram;
use Illuminate\Database\Seeder;

class MentorshipProgramSeeder extends Seeder
{
    public function run(): void
    {
        University::all()->each(function (University $university) {
            MentorshipProgram::factory()
                ->count(rand(1, 2))
                ->create(['university_id' => $university->id]);
        });
    }
}
