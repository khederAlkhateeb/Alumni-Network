<?php

namespace App\V1\Actions\ManagementPassword;

use Illuminate\Support\Facades\Password;

/**
 * Handles the business logic for sending a password reset link.
 *
 * Responsibilities:
 *  - Utilize Laravel's password broker to generate a secure reset token.
 *  - Trigger the notification to dispatch the reset link email to the user.
 *
 * @package App\V1\Actions\ManagementPassword
 */
class SendPasswordResetLink
{
    /**
     * Send the password reset link to the given user email address.
     *
     * @param string $email The target user's email address.
     *
     * @return string The status constant returned by Laravel's password broker.
     */
    public function handle(string $email): string
    {
        return Password::broker()->sendResetLink(
            ['email' => $email]
        );
    }
}
