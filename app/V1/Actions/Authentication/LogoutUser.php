<?php

namespace App\V1\Actions\Authentication;

use App\Models\User;

/**
 * Handles the business logic for logging out an authenticated user.
 *
 * Revokes the Sanctum access token that was used to authenticate
 * the current request, effectively ending the user's session
 * without affecting any other active tokens/devices they may have.
 *
 * @package App\V1\Actions\Authentication
 */
class LogoutUser
{
    /**
     * Revoke the current access token for the given user.
     *
     * @param  User $user The currently authenticated user, resolved
     *                     from the incoming request.
     * @return void
     */
    public function handle(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
