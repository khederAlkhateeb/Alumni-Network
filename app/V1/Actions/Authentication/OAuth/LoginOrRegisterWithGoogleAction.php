<?php

namespace App\V1\Actions\Authentication\OAuth;

use App\Enums\ProfileStatus;
use App\Models\LinkedSocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Handles Google OAuth login/registration.
 *
 * Two distinct "incomplete" concepts are kept separate on purpose:
 *  - No profile at all (alumniProfile/studentProfile both null) means
 *    the user authenticated via Google but hasn't chosen a role/university
 *    yet — they get a scope-limited token to complete that step only.
 *  - is_active = false means the account itself has been deactivated/
 *    suspended, regardless of profile state — this is unrelated to
 *    "incomplete registration" and must never be conflated with it.
 */
class LoginOrRegisterWithGoogleAction
{
    /**
     * Log in or register a user via a verified Google account.
     *
     * @param SocialiteUser $googleUser The user data returned by Socialite.
     *
     * @return array{user: User, token: string, needs_completion: bool}
     *
     * @throws ValidationException If the account is deactivated, or the
     *         user's profile status is "pending" or "suspended".
     */
    public function handle(SocialiteUser $googleUser): array
    {
        return DB::transaction(function () use ($googleUser) {

            $linked = LinkedSocialAccount::query()
                ->where('provider_name', 'google')
                ->where('provider_id', $googleUser->getId())
                ->first();

            $user = $linked?->user
                ?? User::where('email', $googleUser->getEmail())->first();

            if (! $user) {
                $user = User::create([
                    'name'      => $googleUser->getName() ?? $googleUser->getNickname(),
                    'email'     => $googleUser->getEmail(),
                    'password'  => Str::random(32),
                    'is_active' => false,
                ]);
            }

            if (! $linked) {
                $user->linkedSocialAccounts()->firstOrCreate([
                    'provider_name' => 'google',
                    'provider_id'   => $googleUser->getId(),
                ]);
            }

            $profile = $user->alumniProfile ?? $user->studentProfile;

            // No profile yet = registration is incomplete (user hasn't
            // picked a role/university/major). Issue a scope-limited
            // token that can only be used to complete that step —
            // never a full-access token for an unapproved account.
            if (! $profile) {
                $token = $user->createToken(
                    'google-incomplete',
                    ['complete-profile']
                )->plainTextToken;

                return [
                    'user'             => $user,
                    'token'            => $token,
                    'needs_completion' => true,
                ];
            }

            // From here on, the profile exists — same checks as the
            // regular email/password LoginUser action.
            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been suspended.'],
                ]);
            }

            if ($profile->status === ProfileStatus::Pending) {
                throw ValidationException::withMessages([
                    'email' => ['Your account is still pending approval.'],
                ]);
            }

            if ($profile->status === ProfileStatus::Suspended) {
                throw ValidationException::withMessages([
                    'email' => ['Your registration was suspended.'],
                ]);
            }

            $token = $user->createToken('google-login')->plainTextToken;

            return [
                'user'             => $user,
                'token'            => $token,
                'needs_completion' => false,
            ];
        });
    }
}
