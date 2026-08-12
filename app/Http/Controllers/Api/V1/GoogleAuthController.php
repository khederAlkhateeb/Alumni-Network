<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\V1\Actions\Authentication\OAuth\GetGoogleUserAction;
use App\V1\Actions\Authentication\OAuth\LoginOrRegisterWithGoogleAction;
use App\V1\Actions\Authentication\OAuth\RedirectToGoogleAction;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

/**
 * Controller responsible for handling Google OAuth authentication flows.
 *
 * This controller provides two main endpoints:
 * - Redirect to Google's OAuth consent screen.
 * - Callback endpoint that processes the OAuth response, authenticates or registers
 *   the user, and returns an API token.
 *
 * @package App\Http\Controllers\Api\V1
 */
class GoogleAuthController extends Controller
{
    /**
     * Generate the Google OAuth redirect URL.
     *
     * This method delegates to the RedirectToGoogleAction to build the full
     * Google OAuth URL with the appropriate client ID, redirect URI, scopes,
     * and response type (code). The URL is returned as a JSON response
     * so the frontend can open it in a browser or WebView.
     *
     * @param RedirectToGoogleAction $action The action that builds the redirect URL.
     *
     * @return JsonResponse A JSON response containing the redirect URL under the 'redirect_url' key.
     *
     * @example
     * // Request
     * GET /api/v1/auth/google/redirect
     *
     * // Response
     * {
     *     "status": "success",
     *     "data": {
     *         "redirect_url": "https://accounts.google.com/o/oauth2/auth?client_id=...&redirect_uri=..."
     *     }
     * }
     */
    public function redirect(RedirectToGoogleAction $action): JsonResponse
    {
        $url = $action->handle();

        return $this->successResponse([
            'redirect_url' => $url,
        ]);
    }

    /**
     * Handle the OAuth callback from Google.
     *
     * This endpoint receives the authorization code from Google (usually as a
     * `code` query parameter) after the user has granted consent. It uses
     * GetGoogleUserAction to exchange the code for user information, and then
     * LoginOrRegisterWithGoogleAction to either create a new user account or
     * authenticate an existing one. The response contains the authenticated user,
     * an API token for further requests, and a flag indicating whether the user
     * needs to complete additional profile information.
     *
     * @param GetGoogleUserAction            $getGoogleUser     Action to retrieve the Google user from the authorization code.
     * @param LoginOrRegisterWithGoogleAction $loginOrRegister  Action to handle user registration/login and profile validation.
     *
     * @return JsonResponse A JSON response containing:
     *                      - 'user' (object): The authenticated user.
     *                      - 'token' (string): The Sanctum API token.
     *                      - 'needs_completion' (bool): Whether the user profile is incomplete
     *                      (e.g., is_active == false or profile status is pending/suspended).
     *
     * @throws \Illuminate\Validation\ValidationException If the user's profile is pending approval or suspended.
     */
    public function callback(
        GetGoogleUserAction $getGoogleUser,
        LoginOrRegisterWithGoogleAction $loginOrRegister
    ): JsonResponse {
        $googleUser = $getGoogleUser->handle();
        $result     = $loginOrRegister->handle($googleUser);

        return response()->json([
            'user'             => $result['user'],
            'token'            => $result['token'],
            'needs_completion' => $result['needs_completion'],
        ]);
    }
}
