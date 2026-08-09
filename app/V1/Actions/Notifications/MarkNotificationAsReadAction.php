<?php

namespace App\V1\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Mark Notification As Read Action
|--------------------------------------------------------------------------
|
| Marks ONE notification as read. The query is scoped through
| $user->notifications(), so if the notification does not belong
| to the user, findOrFail() throws ModelNotFoundException — the
| Controller catches it and returns a 404 through errorResponse().
|
*/

class MarkNotificationAsReadAction
{
    /**
     * Mark the given notification (owned by $user) as read.
     *
     * @param  User  $user
     * @param  string  $notificationId
     * @return Notification
     */
    public function handle(User $user, string $notificationId): Notification
    {
        /** @var Notification $notification */
        $notification = $user->notifications()->findOrFail($notificationId);

        if (is_null($notification->read_at)) {
            $notification->read_at = now();
            $notification->save();
        }

        return $notification;
    }
}
