<?php

namespace App\V1\Actions\Notifications;

use App\Models\User;

/*
|--------------------------------------------------------------------------
| MarkAllNotificationsAsReadAction
|--------------------------------------------------------------------------
|
| Single responsibility: mark ALL of the user's unread notifications
| as read in one bulk UPDATE query (not a per-row loop).
|
*/

class MarkAllNotificationsAsReadAction
{
    public function __invoke(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
