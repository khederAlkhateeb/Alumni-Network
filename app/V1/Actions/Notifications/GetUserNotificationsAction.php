<?php

namespace App\V1\Actions\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| Get User Notifications Action
|--------------------------------------------------------------------------
|
| Fetches the authenticated user's notifications, newest first,
| paginated (Rule 5.7: 20 items per page).
|
*/

class GetUserNotificationsAction
{
    /**
     * Fetch the given user's notifications.
     *
     * @param  User  $user
     * @return LengthAwarePaginator
     */
    public function handle(User $user): LengthAwarePaginator
    {
        return $user->notifications()->paginate(20);
    }
}
