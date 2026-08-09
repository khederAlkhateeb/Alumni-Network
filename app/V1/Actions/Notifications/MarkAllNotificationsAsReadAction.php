<?php

namespace App\V1\Actions\Notifications;

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Mark All Notifications As Read Action
|--------------------------------------------------------------------------
|
| Marks ALL of the user's unread notifications as read in a single
| bulk UPDATE query (not a per-row loop).
|
*/

class MarkAllNotificationsAsReadAction
{
    /**
     * Mark all of the given user's unread notifications as read.
     *
     * @param  User  $user
     * @return int  Number of notifications marked as read.
     */
    public function handle(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
