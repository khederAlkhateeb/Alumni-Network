<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Only the two participants of a conversation may view its messages.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $user->id === $conversation->user_one_id
            || $user->id === $conversation->user_two_id;
    }
}
