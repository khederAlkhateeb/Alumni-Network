<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UniversityController;
use Illuminate\Http\Request;
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



/*
|--------------------------------------------------------------------------
| University routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->prefix('v1')->group(function () {
    Route::get('/universities', [UniversityController::class, 'index'])->name('api.universities.index');
    Route::post('/universities', [UniversityController::class, 'store'])->name('api.universities.store');
    Route::get('/universities/{university}', [UniversityController::class, 'show'])->name('api.universities.show');
    Route::put('/universities/{university}', [UniversityController::class, 'update'])->name('api.universities.update');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])->name('api.universities.destroy');
});
