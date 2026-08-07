<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

/**
 * Only the two participants of a conversation may subscribe to its
 * private channel — prevents any authenticated user from eavesdropping
 * on a conversation that isn't theirs.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    return $user->id === $conversation->user_one_id
        || $user->id === $conversation->user_two_id;
});
