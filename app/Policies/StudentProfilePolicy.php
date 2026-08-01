<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentProfile;

/**
 * Policy controlling access to StudentProfile resources.
 *
 * This policy ensures:
 * - A student can view or update their own profile.
 * - University staff (admins) can view student profiles belonging to the same university.
 */
class StudentProfilePolicy
{
    /**
     * Check if the authenticated user belongs to the same university
     * as the student whose profile is being accessed.
     *
     * Logic:
     * - Extract student's university via: student → major → faculty → university_id
     * - Extract user's university via:
     *      - user → studentProfile → major → faculty → university_id
     *      - OR fallback to UniversityContext (e.g., admin context)
     *
     * @param User $user            The authenticated user making the request.
     * @param StudentProfile $student The student profile being accessed.
     *
     * @return bool True if both belong to the same university, false otherwise.
     */
    protected function sameUniversity(User $user, StudentProfile $student): bool
    {
        $studentUniversityId = $student->major?->faculty?->university_id;

        $userUniversityId = $user->studentProfile?->major?->faculty?->university_id
            ?? app(\App\Contracts\UniversityContext::class)->getUniversityId();

        return $studentUniversityId !== null
            && $userUniversityId !== null
            && $studentUniversityId === $userUniversityId;
    }

    /**
     * Determine whether the authenticated user can view a student profile.
     *
     * Rules:
     * - A user can view their own profile.
     * - University staff/admins can view profiles of students belonging to their university.
     *
     * @param User $user            The authenticated user.
     * @param StudentProfile $student The student profile being viewed.
     *
     * @return bool True if view is allowed, false otherwise.
     */
    public function view(User $user, StudentProfile $student): bool
    {
        if ($user->id === $student->user_id) {
            return true;
        }

        return $this->sameUniversity($user, $student);
    }

    /**
     * Determine whether the authenticated user can update a student profile.
     *
     * Rules:
     * - Only the owner of the profile can update it.
     *
     * @param User $user            The authenticated user.
     * @param StudentProfile $student The student profile being updated.
     *
     * @return bool True if update is allowed, false otherwise.
     */
    public function update(User $user, StudentProfile $student): bool
    {
        return $user->id === $student->user_id;
    }
}
