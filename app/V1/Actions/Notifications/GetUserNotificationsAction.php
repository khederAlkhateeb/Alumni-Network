<?php

namespace App\V1\Actions\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/*
|--------------------------------------------------------------------------
| GetUserNotificationsAction
|--------------------------------------------------------------------------
|
| Single responsibility: fetch the authenticated user's notifications,
| newest first, paginated (Rule 5.7: 20 items per page).
|
*/

class GetUserNotificationsAction
{
    public function __invoke(User $user): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate(20);
    }
}
