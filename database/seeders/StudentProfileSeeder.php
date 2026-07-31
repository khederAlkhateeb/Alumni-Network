<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Major;
use App\Models\StudentProfile;
use Illuminate\Database\Seeder;

class StudentProfileSeeder extends Seeder
{
    public function run(): void
    {
  
        $students = User::inRandomOrder()->take(10)->get();
        $majorIds = Major::pluck('id');

        foreach ($students as $user) {
            StudentProfile::factory()->create([
                'user_id' => $user->id,
                'major_id' => $majorIds->random(),
            ]);
        }
    }
}
