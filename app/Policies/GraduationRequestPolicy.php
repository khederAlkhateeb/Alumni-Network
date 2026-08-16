<?php

namespace App\Policies;

use App\Enums\GraduationRequestStatus;
use App\Models\GraduationRequest;
use App\Models\User;

/**
 * Policy for authorizing actions on graduation requests.
 */
class GraduationRequestPolicy
{
    /**
     * Perform pre-authorization checks.
     *
     * @param User $user
     * @param string $ability
     * @return bool|null
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any graduation requests.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('uni_admin');
    }

    /**
     * Determine whether the user can create a graduation request.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $user->hasRole('student') && $user->studentProfile !== null;
    }

    /**
     * Determine whether the user can approve the graduation request.
     *
     * @param User $user
     * @param GraduationRequest $graduationRequest
     * @return bool
     */
    public function approve(User $user, GraduationRequest $graduationRequest): bool
    {
        return $user->hasRole('uni_admin')
            && $graduationRequest->status === GraduationRequestStatus::PENDING
            && $this->belongsToSameUniversity($user, $graduationRequest);
    }

    /**
     * Determine whether the user can reject the graduation request.
     *
     * @param User $user
     * @param GraduationRequest $graduationRequest
     * @return bool
     */
    public function reject(User $user, GraduationRequest $graduationRequest): bool
    {
        $hasRole = $user->hasRole('uni_admin');
        $isPending = ($graduationRequest->status === GraduationRequestStatus::PENDING);
        $sameUni = $this->belongsToSameUniversity($user, $graduationRequest);

        if ($hasRole && $isPending && $sameUni) {
            return true;
        }

        \Log::error('Policy Failed', [
            'has_role' => $hasRole,
            'is_pending' => $isPending,
            'status_value' => $graduationRequest->status,
            'same_uni' => $sameUni,
        ]);

        return false;
    }

    /**
     * Helper method to check if admin and student belong to the same university.
     *
     * @param User $adminUser
     * @param GraduationRequest $graduationRequest
     * @return bool
     */
    private function belongsToSameUniversity(User $adminUser, GraduationRequest $graduationRequest): bool
    {
        $graduationRequest->loadMissing('studentProfile.major.faculty');

        $studentProfile = $graduationRequest->studentProfile;

        if (!$studentProfile) {
            return false;
        }

        $studentUniversityId = $studentProfile->major?->faculty?->university_id;

        $adminUniversityId = $adminUser->university_id
            ?? $adminUser->universityAdmin?->university_id;

        \Log::info('University Comparison Debug', [
            'admin_id' => $adminUser->id,
            'admin_university_id' => $adminUniversityId,
            'student_university_id' => $studentUniversityId,
        ]);

        if (!$adminUniversityId || !$studentUniversityId) {
            return false;
        }

        return (int) $adminUniversityId === (int) $studentUniversityId;
    }
}
