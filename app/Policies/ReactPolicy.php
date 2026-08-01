<?php

namespace App\Policies;

use App\Enums\ProfileStatus;
use App\Models\User;

class ReactPolicy
{
    /**
     * Both Alumni and Students can react, as long as their
     * profile status is active (pending/suspended users cannot
     * interact with any content).
     *
     * Uses the null-safe operator (?->) since a user might carry
     * the 'alumni'/'student' role via Spatie while their profile
     * record hasn't been created yet (or was removed) — accessing
     * ->status directly on a null profile would throw a fatal error.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('alumni') && $user->alumniProfile?->status === ProfileStatus::ACTIVE) {
            return true;
        }

        return $user->hasRole('student') && $user->studentProfile?->status === ProfileStatus::ACTIVE;
    }
}
