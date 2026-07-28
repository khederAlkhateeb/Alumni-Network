<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\University;
use Illuminate\Database\Seeder;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        // Ensure there's at least one university to attach faculties to
        $university = University::All();

        $faculties = [
            'Faculty of Engineering',
            'Faculty of Science',
            'Faculty of Arts',
        ];
        if (!$university->isEmpty()) {
            foreach ($university as $university) {
                foreach ($faculties as $name) {
                    Faculty::firstOrCreate([
                        'name' => $name,
                        'university_id' => $university->id,
                    ]);
                }
            }
        }
    }
}
