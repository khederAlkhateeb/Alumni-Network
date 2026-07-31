<?php

namespace App\V1\Actions\StudentProfile;

use App\Models\StudentProfile;

/**
 * Class ShowStudentProfileAction
 *
 * Handles retrieving a single student profile with its related data
 * eager-loaded, avoiding N+1 queries on the caller side.
 */
class ShowStudentProfileAction
{
    /**
     * Execute the student profile retrieval process.
     *
     * @param StudentProfile $profile The student profile to retrieve.
     * @return StudentProfile The profile with 'major' and 'user' loaded.
     */
    public function handle(StudentProfile $profile): StudentProfile
    {
        return $profile->loadMissing(['major', 'user']);
    }
}
