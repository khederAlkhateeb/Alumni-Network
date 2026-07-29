<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\V1\Actions\Authentication\LoginUser;
use App\V1\Actions\Authentication\LogoutUser;
use App\V1\Actions\Authentication\RegisterUser;
use App\V1\Actions\ManagementPassword\ChangePassword;
use App\V1\Actions\ManagementPassword\ResetUserPassword;
use App\V1\Actions\ManagementPassword\SendPasswordResetLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

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
     * @param RegisterUser          $registerUser          Handles new user registration logic.
     * @param LoginUser             $loginUser             Handles user authentication logic.
     * @param LogoutUser            $logoutUser            Handles token revocation on logout.
     * @param ChangePassword        $changePassword        Handles changing authenticated user's password.
     * @param SendPasswordResetLink $sendPasswordResetLink Handles dispatching the reset link email.
     * @param ResetUserPassword     $resetUserPassword     Handles resetting the forgotten password via token.
     */
    public function __construct(
        private readonly RegisterUser $registerUser,
        private readonly LoginUser $loginUser,
        private readonly LogoutUser $logoutUser,
        private readonly ChangePassword $changePassword,
        private readonly SendPasswordResetLink $sendPasswordResetLink,
        private readonly ResetUserPassword $resetUserPassword,
    ) {}

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

    /**
     * Change the authenticated user's password.
     *
     * Validates the user's current password and updates it with the newly
     * provided password. Requires a valid authentication token.
     *
     * @param  ChangePasswordRequest $request The validated password payload.
     * @return JsonResponse HTTP 200 confirming successful password change.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->changePassword->handle($request->user(), $request->validated());

        return $this->successResponse(
            data: null,
            message: 'Password changed successfully.',
            code: 200,
        );
    }

    /**
     * Send a password reset link to the user's email.
     *
     * Uses Laravel's built-in password broker to generate and send a secure
     * reset token. Returns a success message if the email is dispatched.
     *
     * @param  ForgotPasswordRequest $request The validated email payload.
     * @return JsonResponse HTTP 200 on success, or HTTP 400 on failure.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->sendPasswordResetLink->handle($request->validated('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->successResponse(
                data: null,
                message: __($status),
                code: 200,
            );
        }

        // Return standard JSON response for errors, or replace with $this->errorResponse() if you have it.
        return response()->json([
            'message' => __($status),
        ], 400);
    }

    /**
     * Reset user password using a token.
     *
     * Validates the provided token, email, and new password, then updates
     * the user's password in the database and revokes active sessions.
     *
     * @param  ResetPasswordRequest $request The validated reset payload.
     * @return JsonResponse HTTP 200 on success, or HTTP 400 on failure.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->resetUserPassword->handle($request->validated());

        if ($status === Password::PASSWORD_RESET) {
            return $this->successResponse(
                data: null,
                message: __($status),
                code: 200,
            );
        }

        return response()->json([
            'message' => __($status),
        ], 400);
    }
}
