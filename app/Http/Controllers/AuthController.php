<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\V1\Actions\Authentication\LoginUser;
use App\V1\Actions\Authentication\LogoutUser;
use App\V1\Actions\Authentication\RegisterUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles authentication-related HTTP requests, including user
 * registration, login, and logout.
 *
 * Delegates all business logic to dedicated Action classes
 * (RegisterUser, LoginUser, LogoutUser) and is only responsible
 * for request/response orchestration.
 *
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * @param RegisterUser $registerUser Handles new user registration logic.
     * @param LoginUser    $loginUser    Handles user authentication logic.
     * @param LogoutUser   $logoutUser   Handles token revocation on logout.
     */
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser,
        private readonly LogoutUser $logoutUser,
    ) {
    }

    /**
     * Register a new user (alumni or student).
     *
     * The created account is set to a "pending" status and requires
     * admin approval before the user can log in. No authentication
     * token is issued at this stage.
     *
     * @param  RegisterUserRequest $request The validated registration payload.
     * @return JsonResponse HTTP 201 with the created user's data.
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->registerUser->handle($data);
        return $this->successResponse(
            data: $result,
            message: 'Registration successful. Awaiting approval.',
            code: 201,
        );
    }

    /**
     * Authenticate a user and issue an API access token.
     *
     * Validates the provided credentials, ensures the associated
     * alumni/student profile has been approved, and returns a
     * Sanctum personal access token on success.
     *
     * @param  LoginUserRequest $request The validated login credentials.
     * @return JsonResponse HTTP 200 with the user data and access token.
     *
     * @throws \Illuminate\Validation\ValidationException If credentials are invalid
     *         or the account is pending/rejected.
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->loginUser->handle($data);
        return $this->successResponse(
            data: $result,
            message: 'Login successful.',
            code: 200,
        );
    }

    /**
     * Log out the currently authenticated user.
     *
     * Revokes the access token used to authenticate the current
     * request, effectively ending the user's session.
     *
     * @param  Request $request The current HTTP request, used to resolve
     *                          the authenticated user via Sanctum.
     * @return JsonResponse HTTP 200 confirming successful logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->logoutUser->handle($request->user());
        return $this->successResponse(
            data: null,
            message: 'Logged out successfully.',
        );
    }
}
