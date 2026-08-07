<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Enums\ProfileStatus;
use App\Models\User;

class MessagePolicy
{
    /**
     * Both Alumni and Students can send messages, as long as their
     * profile status is active. Whether they're allowed to message
     * this *specific* receiver (accepted Connection or Mentorship)
     * is checked separately inside SendMessageAction, since that
     * check depends on the receiver_id from the validated request.
     */
    public function create(User $user): bool
    {
        $status = match ($user->role) {
            'alumni'  => $user->alumniProfile?->status,
            'student' => $user->studentProfile?->status,
            default   => null,
        };

        return $status === ProfileStatus::ACTIVE;
    }
}
