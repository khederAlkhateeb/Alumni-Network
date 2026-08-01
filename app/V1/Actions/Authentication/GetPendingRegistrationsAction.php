<?php

namespace App\V1\Actions\Authentication;

use App\Models\AlumniProfile;
use App\Models\StudentProfile;
use App\Models\University;
use Illuminate\Support\Collection;

/**
 * Class GetPendingRegistrationsAction
 *
 * Handles aggregating pending alumni and student registrations
 * for a single university's admin approval queue.
 */
class GetPendingRegistrationsAction
{
    /**
     * Execute the pending registrations retrieval process.
     *
     * @param University $university The university whose pending registrations are requested.
     * @return array{alumni: Collection, students: Collection}
     */
    public function handle(University $university): array
    {
        $pendingAlumni = AlumniProfile::query()
            ->pending()
            ->sameUniversityAs($university->id)
            ->with(['user', 'major'])
            ->get();

        $pendingStudents = StudentProfile::query()
            ->pending()
            ->sameUniversityAs($university->id)
            ->with(['user', 'major'])
            ->get();

        return [
            'alumni'   => $pendingAlumni,
            'students' => $pendingStudents,
        ];
    }
}
