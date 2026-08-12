<?php

namespace App\V1\Actions\Notifications;

use App\Models\User;

/**
 * Action to mark all unread notifications of a user as read.
 *
 * Executes a single bulk update query for performance efficiency without using per-row loops.
 */
class MarkAllNotificationsAsReadAction
{
    /**
     * Execute the bulk mark-as-read workflow.
     *
     * @param  User  $user
     * @return int  Number of notifications marked as read.
     */
    public function handle(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
