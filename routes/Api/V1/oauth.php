<?php

use App\Http\Controllers\Api\V1\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Google OAuth Routes
|--------------------------------------------------------------------------
|
| Stateless OAuth flow (Socialite): the client hits `redirect` to obtain
| Google's consent-screen URL, then Google redirects back to `callback`
| with an authorization code, which is exchanged for the user's Google
| profile and used to log in or register them (see
| LoginOrRegisterWithGoogleAction).
|
*/

Route::prefix('auth/google')->group(function () {

    /**
     * GET /auth/google/redirect
     *
     * Returns the Google OAuth consent-screen URL for the client to
     * redirect the user to. Public route — no authentication required,
     * since the user isn't logged in yet at this point.
     */
    Route::get('redirect', [GoogleAuthController::class, 'redirect']);

    /**
     * GET /auth/google/callback
     *
     * Handles Google's redirect back after the user grants consent.
     * Exchanges the authorization code for the Google profile, then
     * logs in or registers the user accordingly. Public route — the
     * user isn't authenticated via our own system until this completes.
     */
    Route::get('callback', [GoogleAuthController::class, 'callback']);

});
