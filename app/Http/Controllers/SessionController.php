<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Handles requests related to the current authenticated session,
 * as opposed to authentication actions themselves (login/logout/register),
 * which live in AuthController.
 *
 * @package App\Http\Controllers
 */
class SessionController extends Controller
{
    /**
     * Return the profile data of the currently authenticated user.
     *
     * Resolves the user from the Sanctum access token attached to
     * the incoming request (via the "auth:api" middleware).
     *
     * @param  Request $request The current HTTP request, carrying
     *                          the authenticated user resolved by Sanctum.
     * @return JsonResponse HTTP 200 with the authenticated user's data.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return $this->successResponse(
            data: $user,
            message: 'Fetched user data',
            code: 200,
        );
    }
}
