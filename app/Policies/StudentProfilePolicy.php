<?php

namespace App\Policies;

use App\Contracts\UniversityContext;
use App\Enums\ProfileStatus;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * Policy controlling access to StudentProfile resources.
 */
class StudentProfilePolicy
{
    /**
     * Bypasses policy checks for Super Admin users.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (app(UniversityContext::class)->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the authenticated user can view a student profile.
     *
     * Rules:
     * - User can view their own profile.
     * - Staff/Admins can view profiles belonging to their active university context.
     */
    public function view(User $user, StudentProfile $student): bool
    {
        //  Owner check
        if ($user->id === $student->user_id) {
            return true;
        }

        //  University match check using Context Contract
        $contextUniversityId = app(UniversityContext::class)->getUniversityId();
        $studentUniversityId = $student->major?->faculty?->university_id;

        return $contextUniversityId !== null
            && $studentUniversityId !== null
            && $contextUniversityId === $studentUniversityId;
    }

    /**
     * Determine whether the authenticated user can update a student profile.
     *
     * Rules:
     * - Only the owner of the profile can update it.
     */
    public function update(User $user, StudentProfile $student): bool
    {
        return $user->id === $student->user_id;
    }



    public function submitGraduationRequest(User $user, StudentProfile $studentProfile): bool
    {
        // 1. يجب أن يكون السجل عائد للشيء نفسه
        // 2. حالة الطالب الحالية ACTIVE
        // 3. وصل لسنة التخرج المتوقعة أو تجاوزها
        return $user->id === $studentProfile->user_id
            && $studentProfile->status === ProfileStatus::ACTIVE
            && (int) date('Y') >= $studentProfile->expected_graduation_year;
    }
}
