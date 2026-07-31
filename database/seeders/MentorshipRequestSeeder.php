<?php

namespace Database\Seeders;

use App\Models\AlumniProfile;
use App\Models\User;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use Illuminate\Database\Seeder;


  class MentorshipRequestSeeder extends Seeder
{
    public function run(): void
    {
        $mentorUserIds = AlumniProfile::where('is_open_to_mentor', true)->pluck('user_id');
        $allUserIds = User::pluck('id');
        $programIds = MentorshipProgram::pluck('id');

        if ($mentorUserIds->isEmpty()) {
            $this->command?->warn('  No alumni_profiles has  is_open_to_mentor=true in  MentorshipRequestSeeder.');
            return;
        }

        for ($i = 0; $i < 50; $i++) {

            $mentorId = $mentorUserIds->random();
            $menteeId = $allUserIds->reject(fn ($id) => $id === $mentorId)->random();
            $programId = $programIds->random();


            if (MentorshipRequest::where('program_id', $programId)
                ->where('mentor_id', $mentorId)
                ->where('mentee_id', $menteeId)
                ->exists()) {
                continue;
            }

            MentorshipRequest::factory()->create([
                'program_id' => $programId,
                'mentor_id' => $mentorId,
                'mentee_id' => $menteeId,
            ]);
        }
    }
}
