<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\RegistrationManagementController;

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

Route::prefix('v1/auth')->group(function () {
    /**
     * Register a new user (alumni or student).
     * @see AuthController::register()
     */
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');

    /**
     * Authenticate an existing, approved user and issue an access token.
     * @see AuthController::login()
     */
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
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
Route::middleware('auth:api')->prefix('v1/auth')->group(function () {
    /**
     * Revoke the access token used for the current request.
     * @see AuthController::logout()
     */
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');

    /**
     * Return the currently authenticated user's data.
     * @see SessionController::me()
     */
    Route::get('/me', [SessionController::class, 'me'])->name('api.auth.me');





});
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    foreach (File::allFiles(__DIR__ . '/Api/V1') as $file) {
        require $file->getPathname();
    }

});


/*
|--------------------------------------------------------------------------
| University Admin registration management routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'role:uni_admin'])->prefix('v1/uni_admin')->group(function () {
    /**
     * Approve a user's registration for a specific university.
     * @see RegistrationManagementController::approveUser()
     */
    Route::post('universities/{university}/registrations/{user}/approve', [RegistrationManagementController::class, 'approveUser'])->name('api.registrations.approve');

    /**
     * Reject a user's registration for a specific university.
     * @see RegistrationManagementController::rejectUser()
     */
    Route::post('universities/{university}/registrations/{user}/reject', [RegistrationManagementController::class, 'rejectUser'])->name('api.registrations.reject');
});

Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::resource('faculties', FacultyController::class);

});
