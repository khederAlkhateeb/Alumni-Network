<?php

namespace App\Policies;

use App\Models\MentorshipRequest;
use App\Models\User;

class MentorshipRequestPolicy
{
    /**
     * Grant all permissions to super_admin.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the mentorship request.
     */
    public function view(User $user, MentorshipRequest $mentorshipRequest): bool
    {
        return $user->id === $mentorshipRequest->mentor_id
            || $user->id === $mentorshipRequest->mentee_id;
    }

    /**
     * Determine whether the user can update the request status (Accept/Reject/Complete).
     */
    public function updateStatus(User $user, MentorshipRequest $mentorshipRequest): bool
    {
        return $user->id === $mentorshipRequest->mentor_id;
    }
}
