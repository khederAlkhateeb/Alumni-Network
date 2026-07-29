<?php

namespace App\V1\Actions\ManagementPassword;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles the business logic for changing an authenticated user's password.
 *
 * Responsibilities:
 *  - Verify that the provided current password matches the user's actual password.
 *  - Update the user's password (hashed automatically via the model's `hashed` cast).
 *
 * @package App\V1\Actions\ManagementPassword
 */
class ChangePassword
{
    /**
     * Change the authenticated user's password.
     *
     * @param User $user The currently authenticated user model instance.
     * @param array{
     *      current_password: string,
     *      password: string
     * } $data The validated payload containing old and new passwords.
     *
     * @throws ValidationException If the current password fails verification.
     * @return void
     */
    public function handle(User $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided current password does not match our records.'],
            ]);
        }

        $user->update([
            'password' => $data['password'],
        ]);
    }
}
