<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class AlumniProfileSeeder extends Seeder
{
    private const DEFAULT_MAJOR_ID = 1;

    public function run(): void
    {
            $user = User::firstOrCreate(
            ['email' => 'alumni@test.com'],
            [
                'name'      => 'Test Alumni',
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]
        );


        if (! $user->hasRole('alumni')) {
            $user->assignRole('alumni');
        }


        AlumniProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'major_id'          => self::DEFAULT_MAJOR_ID,
                'student_number'    => '2019123456',
                'graduation_year'   => 2023,
                'current_job_title' => 'Backend Developer',
                'current_company'   => 'Tech Solutions',
                'linkedin_url'      => 'https://linkedin.com/in/test-alumni',
                'bio'               => 'Passionate backend developer.',
                'city'              => 'Ramallah',
                'country'           => 'Palestine',
                'status'            => 'active',
                'is_open_to_mentor' => false,
            ]
        );
    }
}
