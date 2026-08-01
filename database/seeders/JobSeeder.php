<?php

namespace Database\Seeders;

use App\Models\JobListing;
use App\Models\University;
use App\Models\User;
use App\Models\Job;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    public function run(): void
    {
        $universityIds = University::pluck('id');
        $userIds = User::pluck('id');

        JobListing::factory(10)->make()->each(function (JobListing $job) use ($universityIds, $userIds) {
            $job->university_id = $universityIds->random();
            $job->posted_by_user_id = $userIds->random();
            $job->save();
        });
    }
}
