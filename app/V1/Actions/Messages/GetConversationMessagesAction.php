<?php

namespace App\V1\Actions\Messages;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\CursorPaginator;

class GetConversationMessagesAction
{
    /**
     * Retrieve a paginated list of messages in a conversation,
     * most recent first, with each message's sender eager-loaded.
     *
     * @param Conversation $conversation The conversation to list messages for.
     *
     * @return CursorPaginator
     */
    public function handle(Conversation $conversation): CursorPaginator
    {
        return $conversation->messages()
            ->with('sender')
            ->latest()
            ->cursorPaginate(20);
    }
}
