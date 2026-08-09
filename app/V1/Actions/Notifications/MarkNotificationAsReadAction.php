<?php

namespace App\V1\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| MarkNotificationAsReadAction
|--------------------------------------------------------------------------
|
| Single responsibility: mark ONE notification as read.
| The query is scoped through $user->notifications(), so if the
| notification does not belong to the user, findOrFail() throws
| ModelNotFoundException — the Controller catches it and returns
| a 404 through errorResponse().
|
| Note: this project uses a custom App\Models\Notification model
| (plain Eloquent, with a direct 'user_id' column), NOT Laravel's
| built-in Illuminate\Notifications\DatabaseNotification — the
| return type must reflect that, or PHP throws a TypeError on
| return that gets masked as a generic 500 by the Controller's
| catch (Throwable) block.
|
*/

class MarkNotificationAsReadAction
{
    public function __invoke(User $user, string $notificationId): Notification
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
