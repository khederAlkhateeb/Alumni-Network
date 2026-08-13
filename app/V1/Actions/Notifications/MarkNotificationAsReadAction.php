<?php

namespace App\V1\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Validation\ValidationException;

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
     * @throws ValidationException
     */
    public function handle(User $user, string $notificationId): Notification
    {
        /** @var Notification|null $notification */
        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            throw ValidationException::withMessages([
                'notification_id' => ['This notification does not exist or does not belong to you.'],
            ]);
        }

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }
}
