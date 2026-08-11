<?php

namespace App\Builders;

use Illuminate\Database\Eloquent\Builder;
use App\Models\Message;

/**
 * Class ConversationQueryBuilder
 *
 * Custom Eloquent query builder for handling conversation-specific scopes and queries.
 */
class ConversationQueryBuilder extends Builder
{
    /**
     * Scope the query to only include conversations involving the specified user.
     *
     * @param int $userId The ID of the user.
     * @return $this
     */
    public function forUser(int $userId)
    {
        return $this->where(function ($q) use ($userId) {
            $q->where('user_one_id', $userId)
              ->orWhere('user_two_id', $userId);
        });
    }

    /**
     * Eager load the latest message for each conversation.
     *
     * @return $this
     */
    public function withLastMessage()
    {
        return $this->with([
            'messages' => fn($q) => $q->latest()->limit(1)
        ]);
    }

    /**
     * Count unread messages for a specific user within the conversations.
     *
     * @param int $userId The ID of the authenticated user checking unread messages.
     * @return $this
     */
    public function withUnreadCount(int $userId)
    {
        return $this->withCount([
            'messages as unread_count' => fn($q) =>
                $q->whereNull('read_at')
                  ->where('sender_id', '!=', $userId)
        ]);
    }

    /**
     * Eager load both participants (user one and user two) of the conversations.
     *
     * @return $this
     */
    public function withOtherUser()
    {
        return $this->with(['userOne', 'userTwo']);
    }

    /**
     * Order conversations by the timestamp of their most recent message in descending order.
     *
     * @return $this
     */
    public function orderByLatestMessage()
    {
        return $this->orderBy(
            Message::select('created_at')
                ->whereColumn('conversation_id', 'conversations.id')
                ->latest()
                ->limit(1),
            'desc'
        );
    }
    /**
     * Scope the query to find a conversation between two specific users.
     *
     * @param int $userOneId The first user ID.
     * @param int $userTwoId The second user ID.
     * @return $this
     */
    public function betweenUsers(int $userOneId, int $userTwoId)
    {
        return $this->where(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userOneId)
                  ->where('user_two_id', $userTwoId);
        })->orWhere(function ($query) use ($userOneId, $userTwoId) {
            $query->where('user_one_id', $userTwoId)
                  ->where('user_two_id', $userOneId);
        });
    }
}
