<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    public function run(): void
    {
        Faculty::all()->each(function (Faculty $faculty) {
            Major::factory()
                ->count(rand(2, 4))
                ->create(['faculty_id' => $faculty->id]);
        });
    }
}
