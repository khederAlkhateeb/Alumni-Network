<?php

namespace App\V1\Actions\Messages;

use App\Builders\ConversationQueryBuilder;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class ListConversationsAction
 *
 * Handles fetching a paginated list of conversations for a specific user,
 * including the last message, unread count, and the other participant's details.
 */
class ListConversationsAction
{
    /**
     * Execute the action to retrieve and sort user conversations.
     *
     * @param User|int $user The authenticated user instance or user ID.
     * @param int|null $per_page The number of items per page.
     * @return LengthAwarePaginator Paginated collection of conversations.
     */
    public function handle(User|int $user, ?int $per_page = null): LengthAwarePaginator
    {
        $per_page = $filters['per_page']
            ?? $per_page
            ?? config('app.pagination.per_page');

        // Ensure we get the user ID correctly whether an ID or a Model was passed
        $userId = $user instanceof User ? $user->id : $user;

        return Conversation::query()
            ->forUser($userId)
            ->withLastMessage()
            ->withUnreadCount($userId)
            ->withOtherUser()
            ->orderByLatestMessage()
            ->paginate((int) $per_page);
    }
}
