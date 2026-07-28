<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\Major;
use App\Models\User;
use Illuminate\Database\Seeder;

class AlumniProfileSeeder extends Seeder
{
    public function run(): void
    {

        $major = Major::first() ?? Major::factory()->create();

 
        $alumniData = [
            [
                'name'               => 'Test Alumni One',
                'email'              => 'alumni@test.com',
                'student_number'     => '2019123456',
                'graduation_year'    => 2023,
                'current_job_title'  => 'Backend Developer',
                'current_company'    => 'Tech Solutions',
                'linkedin_url'       => 'https://linkedin.com/in/test-alumni-1',
                'bio'                => 'Passionate Laravel backend developer.',
                'city'               => 'Damascus',
                'country'            => 'Syria',
                'status'             => 'active',
                'is_open_to_mentor'  => true,
            ],
            [
                'name'               => 'Sarah Mansour',
                'email'              => 'sarah@test.com',
                'student_number'     => '2020112233',
                'graduation_year'    => 2024,
                'current_job_title'  => 'Frontend Engineer',
                'current_company'    => 'Creative Apps',
                'linkedin_url'       => 'https://linkedin.com/in/sarah-mansour',
                'bio'                => 'UI/UX enthusiast and React developer.',
                'city'               => 'Aleppo',
                'country'            => 'Syria',
                'status'             => 'active',
                'is_open_to_mentor'  => false,
            ],
            [
                'name'               => 'Karem Ahmad',
                'email'              => 'karem@test.com',
                'student_number'     => '2018445566',
                'graduation_year'    => 2022,
                'current_job_title'  => 'DevOps Engineer',
                'current_company'    => 'Cloud Systems',
                'linkedin_url'       => 'https://linkedin.com/in/karem-ahmad',
                'bio'                => 'Automating deployments and cloud infrastructure.',
                'city'               => 'Homs',
                'country'            => 'Syria',
                'status'             => 'pending',
                'is_open_to_mentor'  => true,
            ],
            [
                'name'               => 'Nour Hasan',
                'email'              => 'nour@test.com',
                'student_number'     => '2021998877',
                'graduation_year'    => 2025,
                'current_job_title'  => 'Data Analyst',
                'current_company'    => 'Data Corp',
                'linkedin_url'       => 'https://linkedin.com/in/nour-hasan',
                'bio'                => 'Extracting insights from complex datasets.',
                'city'               => 'Latakia',
                'country'            => 'Syria',
                'status'             => 'active',
                'is_open_to_mentor'  => false,
            ],
        ];

        // 3. الدوران على البيانات وإنشاء الـ Users والـ AlumniProfiles المربوطة بهم
        foreach ($alumniData as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'      => $data['name'],
                    'password'  => bcrypt('password'), // كلمة السر الموحدة لكل حسابات التجربة
                    'is_active' => true,
                ]
            );

            if (! $user->hasRole('alumni')) {
                $user->assignRole('alumni');
            }

            AlumniProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'major_id'          => $major->id,
                    'student_number'    => $data['student_number'],
                    'graduation_year'   => $data['graduation_year'],
                    'current_job_title' => $data['current_job_title'],
                    'current_company'   => $data['current_company'],
                    'linkedin_url'      => $data['linkedin_url'],
                    'bio'               => $data['bio'],
                    'city'              => $data['city'],
                    'country'           => $data['country'],
                    'status'            => $data['status'],
                    'is_open_to_mentor' => $data['is_open_to_mentor'],
                ]
            );
        }
    }
}
