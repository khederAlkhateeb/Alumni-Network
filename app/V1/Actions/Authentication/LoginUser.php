<?php

namespace App\V1\Actions\Authentication;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Handles the business logic for authenticating a user and issuing
 * an API access token.
 *
 * Responsibilities:
 *  - Verify the provided credentials (email + password).
 *  - Ensure the user's associated profile (alumni or student) has
 *    been approved by an admin before allowing access.
 *  - Issue a Sanctum personal access token on success.
 *
 * @package App\V1\Actions\Authentication
 */
class LoginUser
{
    /**
     * Authenticate a user with the given credentials.
     *
     * @param  array{email: string, password: string} $data The validated
     *         login payload containing the user's email and password.
     *
     * @return array{user: User, token: string} The authenticated user
     *         instance and a newly issued plain-text Sanctum token.
     *
     * @throws ValidationException If:
     *         - No user exists with the given email, or the password
     *           does not match (generic "Invalid credentials" message
     *           to avoid leaking which field was incorrect).
     *         - The user's alumni/student profile status is "pending"
     *           (awaiting admin approval).
     *         - The user's alumni/student profile status is "rejected".
     */
    public function handle(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'credentials' => ['Invalid credentials.'],
            ]);
        }
        if(!$user->is_active)
        {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended.'],
            ]);
        }
        $profile = $user->alumniProfile ?? $user->studentProfile;

        if ($profile && $profile->status === 'pending') {
            throw ValidationException::withMessages([
                'email' => ['Your account is still pending approval.'],
            ]);
        }
        if ($profile && $profile->status === 'rejected') {
            throw ValidationException::withMessages([
                'email' => ['Your registration was rejected.'],
            ]);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'user'  => $user,
            'token' => $token,
        ];
    }
}
