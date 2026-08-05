<?php

namespace App\Policies;

use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MentorshipRequestPolicy
{

    /**
     * Determine whether the user can view the model.
     */
  public function view(User $user, MentorshipRequest $mentorshipRequest): bool
    {
        return $user->id === $mentorshipRequest->mentor_id || $user->id === $mentorshipRequest->mentee_id;
    }

    public function updateStatus(User $user, MentorshipRequest $mentorshipRequest): bool
    {
        return $user->id === $mentorshipRequest->mentor_id;
    }
  

    }
