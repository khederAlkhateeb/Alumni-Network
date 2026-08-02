<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\AlumniProfile;
use Illuminate\Database\Seeder;

class AlumniProfileSeeder extends Seeder
{
    public function run(): void
    {
        $studentUserIds = StudentProfile::pluck('user_id');

        $alumniUsers = User::whereNotIn('id', $studentUserIds)
            ->inRandomOrder()
            ->take(100)//limt
            ->get();

        $majorIds = Major::pluck('id');


        foreach ($alumniUsers as $index => $user) {
            $factory = AlumniProfile::factory();

            if ($index < 5) {
                $factory = $factory->mentor();
            }

            $factory->create([
                'user_id' => $user->id,
                'major_id' => $majorIds->random(),
            ]);
        }
    }
}
