<?php

namespace App\Policies;

use App\Models\User;
use App\Models\University;

class RegistrationPolicy
{

    /**
     * Determine if the admin can approve the registration.
     * @param User $admin The university admin attempting to approve the registration.
     * @param User $targetUser The user whose registration is being approved.
     * @param University $university The university for which the registration is being approved.
     * @return bool True if the admin can approve the registration, false otherwise.
     */
    public function approve(User $admin, User $targetUser, University $university): bool
    {
        if (!$admin->hasRole('uni_admin')) {
            return false;
        }

        $adminUniversityId = $admin->universityAdmin?->university_id;
        if (!$adminUniversityId || (int)$adminUniversityId !== (int)$university->id) {
            return false;
        }

        $targetProfile = $targetUser->alumniProfile ?? $targetUser->studentProfile;
        if (!$targetProfile) {
            return false;
        }

        if ($targetProfile->status !== 'pending') {
            return false;
        }

        $targetUniversityId = $targetProfile->major?->faculty?->university_id;

        return (int)$targetUniversityId === (int)$university->id;
    }

    /**
     * Determine if the admin can reject the registration.
     * @param User $admin The university admin attempting to reject the registration.
     * @param User $targetUser The user whose registration is being rejected.
     * @param University $university The university for which the registration is being rejected.
     * @return bool True if the admin can reject the registration, false otherwise.
     */
    public function reject(User $admin, User $targetUser, University $university): bool
    {
        return $this->approve($admin, $targetUser, $university);   
    }
}
