<?php

namespace App\V1\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Action to mark a specific notification as read.
 *
 * Scopes the query through the user's notifications to ensure ownership security.
 */
class MarkNotificationAsReadAction
{
    /**
     * Execute the single notification mark-as-read workflow.
     *
     * @param  User  $user
     * @param  string  $notificationId
     * @return Notification
     *
     * @throws ModelNotFoundException
     */
    public function handle(User $user, string $notificationId): Notification
    {
        /** @var Notification $notification */
        $notification = $user->notifications()->findOrFail($notificationId);

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }
}
