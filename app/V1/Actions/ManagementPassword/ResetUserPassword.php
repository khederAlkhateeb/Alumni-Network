<?php

namespace App\V1\Actions\ManagementPassword;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Event;

/**
 * Handles the business logic for resetting a user's forgotten password.
 *
 * Responsibilities:
 *  - Validate the reset token and user credentials via Laravel's password broker.
 *  - Update the user's password record (automatically hashed by model cast).
 *  - Refresh the user's `remember_token` to secure active sessions.
 *  - Fire the `PasswordReset` system event.
 *
 * @package App\V1\Actions\ManagementPassword
 */
class ResetUserPassword
{
    /**
     * Reset the user's password using the provided reset token.
     *
     * @param array{
     *      token: string,
     *      email: string,
     *      password: string
     * } $data The validated payload containing reset parameters.
     *
     * @return string The status constant returned by Laravel's password broker.
     */
    public function handle(array $data): string
    {
        return Password::broker()->reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                Event::dispatch(new PasswordReset($user));
            }
        );
    }
}
