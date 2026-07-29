<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SessionController;
use App\Http\Controllers\Api\V1\RegistrationManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public authentication routes
|--------------------------------------------------------------------------
|
| These endpoints do not require authentication and are accessible
| to any visitor. New accounts are created with a "pending" status
| and cannot log in until approved by an admin.
|
*/
Route::prefix('auth')->group(function () {
    /**
     * Register a new user (alumni or student).
     * @see AuthController::register()
     */
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
    /**
     * Authenticate an existing, approved user and issue an access token.
     * @see AuthController::login()
     */
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    /**
     * Send a password reset link to the user's email.
     * @see AuthController::forgotPassword()
     */
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.v1.auth.forgot-password');
    /**
     * Reset user password using a token.
     * @see AuthController::resetPassword()
     */
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('api.v1.auth.reset-password');
});

/*
|--------------------------------------------------------------------------
| Protected authentication routes
|--------------------------------------------------------------------------
|
| These endpoints require a valid Sanctum access token (guard "api",
| driver "sanctum" — see config/auth.php). The token is passed via
| the "Authorization: Bearer {token}" header.
|
*/
Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    /**
     * Revoke the access token used for the current request.
     * @see AuthController::logout()
     */
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
    /**
     * Return the currently authenticated user's data.
     * @see SessionController::me()
     */
    Route::get('/me', [SessionController::class, 'me'])->name('api.v1.auth.me');
    /**
     * Change the authenticated user's password.
     * @see AuthController::changePassword()
     */
    Route::put('/change-password', [AuthController::class, 'changePassword'])->name('api.v1.auth.password.change');
});

/*
|--------------------------------------------------------------------------
| University Admin registration management routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:uni_admin'])->prefix('uni_admin')->group(function () {
    /**
     * Approve a user's registration for a specific university.
     * @see RegistrationManagementController::approveUser()
     */
    Route::post('universities/{university}/registrations/{user}/approve', [RegistrationManagementController::class, 'approveUser'])->name('api.v1.registrations.approve');
    /**
     * Reject a user's registration for a specific university.
     * @see RegistrationManagementController::rejectUser()
     */
    Route::post('universities/{university}/registrations/{user}/reject', [RegistrationManagementController::class, 'rejectUser'])->name('api.v1.registrations.reject');
});