<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    public function run(): void
    {

        University::all()->each(function (University $university) {
            Faculty::factory()
                ->count(rand(3, 6))
                ->create(['university_id' => $university->id]);
        });
    }
}
