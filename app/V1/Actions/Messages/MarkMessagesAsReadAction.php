<?php

namespace App\V1\Actions\Messages;

use App\Models\Message;
use App\Models\Conversation;
use App\Events\MessagesWereRead;

/**
 * Class MarkMessagesAsReadAction
 *
 * Handles marking ALL unread messages from a specific sender within a conversation
 * as read by the authenticated user in a single batch query,
 * and dispatches a single broadcast event upon success.
 */
class MarkMessagesAsReadAction
{
    /**
     * Execute the action to batch-update unread messages status and trigger the broadcast.
     *
     * @param int $authUserId The ID of the authenticated user reading the messages.
     * @param int $senderId The ID of the user who sent the messages.
     * @return void
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the conversation is not found.
     */
    public function handle(int $authUserId, int $senderId): void
    {
        $conversation = Conversation::betweenUsers($authUserId, $senderId)->firstOrFail();

        $updated = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', $senderId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            broadcast(new MessagesWereRead($conversation->id, $authUserId, $senderId));
        }
    }
}
