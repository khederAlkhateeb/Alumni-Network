<?php

namespace App\V1\Actions\StudentProfile;

use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

/**
 * Class UpdateStudentProfileAction
 *
 * Handles updating a student profile's editable fields.
 */
class UpdateStudentProfileAction
{
    /**
     * Execute the student profile update process.
     *
     * @param StudentProfile $profile The student profile being updated.
     * @param array $data Validated payload containing the fields to update.
     * @return StudentProfile The refreshed student profile instance.
     *
     * @throws \Throwable If database transaction fails.
     */
    public function handle(StudentProfile $profile, array $data): StudentProfile
    {
        return DB::transaction(function () use ($profile, $data) {

          $profile->update([
                'major_id'                 => $data['major_id'] ?? $profile->major_id,
                'enrollment_number'        => $data['enrollment_number'] ?? $profile->enrollment_number,
                'enrollment_year'          => $data['enrollment_year'] ?? $profile->enrollment_year,
                'expected_graduation_year' => $data['expected_graduation_year'] ?? $profile->expected_graduation_year,
            ]);
            return $profile->fresh(['major']);
        });
    }
}
