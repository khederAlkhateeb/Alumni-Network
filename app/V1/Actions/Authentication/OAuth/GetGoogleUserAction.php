<?php

namespace App\V1\Actions\Authentication\OAuth;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Action class that retrieves user information from Google using an authorization code.
 *
 * This action is responsible for exchanging the OAuth authorization code
 * (obtained from the redirect callback) for a user's information from Google.
 * It acts as the second step in the Google OAuth flow, returning a hydrated
 * `SocialiteUser` object containing the user's details such as ID, name,
 * email, and avatar.
 *
 * The action relies on Laravel Socialite's `stateless()` method, ensuring it
 * works seamlessly with API-driven authentication where no session state is
 * maintained.
 *
 * @package App\V1\Actions\Authentication\OAuth
 */
class GetGoogleUserAction
{
    /**
     * Exchange the authorization code for a Google user object.
     *
     * This method assumes that the request contains the `code` query parameter
     * provided by Google after the user has granted consent. It uses the
     * Socialite facade to retrieve the user data. The returned object can be
     * used to create or authenticate a local user account.
     *
     * @return SocialiteUser The authenticated Google user object containing
     *                       provider ID, name, email, avatar, and other details.
     *
     * @throws \Laravel\Socialite\Two\InvalidStateException If the OAuth state
     *         does not match (though with `stateless()` this is less common).
     * @throws \Exception If the authorization code is invalid or expired.
     *
     * @example
     * ```php
     * // Assume the current request has a valid 'code' parameter.
     * $action = new GetGoogleUserAction();
     * $googleUser = $action->handle();
     *
     * $email = $googleUser->getEmail();     // "user@example.com"
     * $name  = $googleUser->getName();      // "John Doe"
     * $id    = $googleUser->getId();        // "1234567890"
     * ```
     */
    public function handle(): SocialiteUser
    {
        return Socialite::driver('google')->stateless()->user();
    }
}
