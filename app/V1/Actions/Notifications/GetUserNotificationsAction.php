<?php

namespace App\V1\Actions\Notifications;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to retrieve a paginated list of user notifications.
 *
 * Fetches the authenticated user's notifications ordered by newest first,
 * with configurable pagination items per page.
 */
class GetUserNotificationsAction
{
    /**
     * Execute the notification listing workflow.
     *
     * @param  User  $user
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<int, Notification>
     */
    public function handle(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('app.pagination.per_page', 20);

        return $user->notifications()
            ->latest()
            ->paginate((int) $perPage);
    }
}
