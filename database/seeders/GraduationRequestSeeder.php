<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GraduationRequest;
use App\Models\User;
use App\Models\StudentProfile;

/**
 * Seeder for populating sample graduation request data.
 */
class GraduationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a student user and profile
        $student = User::factory()->create(['is_active' => true]);
        $profile = StudentProfile::factory()->create(['user_id' => $student->id]);

        // Create a graduation request record
        GraduationRequest::factory()->create([
            'user_id'            => $student->id,
            'student_profile_id' => $profile->id,
            'certificate_path'   => 'certificates/sample.pdf',
            'status'             => 'pending',
        ]);
    }
}
