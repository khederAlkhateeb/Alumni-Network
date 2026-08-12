<?php

namespace App\V1\Actions\Authentication\OAuth;

use Laravel\Socialite\Facades\Socialite;

/**
 * Action class responsible for generating the Google OAuth redirect URL.
 *
 * This action serves as the first step in the Google OAuth authentication flow.
 * It creates a stateless redirect URL to Google's OAuth consent screen, which
 * includes the necessary query parameters: client ID, redirect URI, scopes,
 * and response type. The resulting URL should be used by the frontend
 * application (or mobile app) to initiate the login process by redirecting
 * the user to Google.
 *
 * @package App\V1\Actions\Authentication\OAuth
 */
class RedirectToGoogleAction
{
    /**
     * Generate the Google OAuth redirect URL.
     *
     * This method uses Laravel Socialite to build a stateless OAuth URL for
     * Google. The `stateless()` method is crucial for API-based authentication
     * because it prevents the use of sessions, which are not suitable for
     * stateless APIs (e.g., mobile apps, SPAs). The returned URL contains all
     * the required parameters configured in the `config/services.php` file
     * under the 'google' provider.
     *
     * @return string The full URL to redirect the user to Google's consent page.
     *
     * @example
     * ```php
     * $action = new RedirectToGoogleAction();
     * $redirectUrl = $action->handle();
     * // Returns: "https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=..."
     * ```
     */
    public function handle(): string
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }
}
