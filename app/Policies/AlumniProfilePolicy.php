<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlumniProfile;
use App\Models\User;

class AlumniProfilePolicy
{
    /**
     * Grant all permissions to super_admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null; // Fallthrough to specific policy methods
    }

    /**
     * GET /api/v1/alumni
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * GET /api/v1/alumni/{id}
     */
    public function view(User $user, AlumniProfile $profile): bool
    {
        return true;
    }

    /**
     * PUT /api/v1/alumni/me
     */
    public function update(User $user, AlumniProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    /**
     * POST /api/v1/alumni/me/toggle-mentor
     */
    public function toggleMentor(User $user, AlumniProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    /**
     * POST /api/v1/alumni/me/complete-profile
     */
    public function completeProfile(User $user, AlumniProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
