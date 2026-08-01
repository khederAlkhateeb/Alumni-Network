<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\JobListing;
use App\Models\User;
use App\Models\JobApplication;
use Illuminate\Database\Seeder;

class JobApplicationSeeder extends Seeder
{
    public function run()
{
    $jobIds = JobListing::pluck('id');
    $userIds = User::pluck('id');

    foreach ($jobIds as $jobId) {
        JobApplication::create([
            'job_listing_id' => $jobId,
            'applicant_id' => $userIds->random(),
            'cover_letter' => fake()->paragraph(5),
            'resume' => 'resumes/' . fake()->uuid() . '.pdf',
            'status' => fake()->randomElement(['submitted', 'shortlisted', 'rejected']),
        ]);
    }
}
}
