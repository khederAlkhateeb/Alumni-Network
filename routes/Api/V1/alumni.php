<?php

use App\Http\Controllers\Api\V1\AlumniProfileController;
use App\Http\Middleware\EnsureTokenIsFullAccess;
use App\Http\Middleware\EnsureProfileIsActive;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Alumni Profile Routes (Protected by Sanctum + Role: alumni)
 * --------------------------------------------------------------------------
 *
 * These routes allow authenticated alumni users to:
 * - View their own profile
 * - Update their profile
 * - Manage mentor status
 * - Complete profile details
 * - View other alumni profiles
 *
 * Authorization:
 * - All routes require:
 *      - Valid Sanctum token
 *      - User role: alumni
 *      - Active Profile (EnsureProfileIsActive)
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\AlumniProfileController
 */
Route::middleware(['auth:sanctum', 'role:alumni', EnsureProfileIsActive::class])->group(function () {

    /**
     * Retrieve a list of alumni profiles.
     *
     * Endpoint:
     * - GET /api/v1/alumni
     *
     * Authorization:
     * - permission: view-alumni-profiles
     *
     * @see AlumniProfileController::index()
     */
    Route::get('alumni', [AlumniProfileController::class, 'index'])
        ->middleware('permission:view-alumni-profiles')
        ->name('alumni.index');

    /**
     * Retrieve the authenticated alumni user's own profile.
     *
     * Endpoint:
     * - GET /api/v1/alumni/me
     *
     * Authorization:
     * - permission: view-alumni-profiles
     *
     * @see AlumniProfileController::showMe()
     */
    Route::get('alumni/me', [AlumniProfileController::class, 'showMe'])
        ->withoutMiddleware([EnsureTokenIsFullAccess::class])
        ->middleware('permission:view-alumni-profiles')
        ->name('alumni.me.show');

    /**
     * Update the authenticated alumni user's profile.
     *
     * Endpoint:
     * - PUT /api/v1/alumni/me/updateMe
     *
     * Authorization:
     * - permission: edit-own-profile
     *
     * @see AlumniProfileController::updateMe()
     */
    Route::put('alumni/me/updateMe', [AlumniProfileController::class, 'updateMe'])
        ->withoutMiddleware([EnsureTokenIsFullAccess::class])
        ->middleware('permission:edit-own-profile')
        ->name('alumni.me.update');

    /**
     * Toggle mentor status for the authenticated alumni user.
     *
     * Endpoint:
     * - POST /api/v1/alumni/me/toggle-mentor
     *
     * Authorization:
     * - permission: toggle-mentor-status
     *
     * @see AlumniProfileController::toggleMentor()
     */
    Route::post('alumni/me/toggle-mentor', [AlumniProfileController::class, 'toggleMentor'])
        ->middleware('permission:toggle-mentor-status')
        ->name('alumni.me.toggle-mentor');


    /**
     * Complete the authenticated alumni user's profile.
     *
     * Endpoint:
     * - POST /api/v1/alumni/me/complete-profile
     *
     * Authorization:
     * - permission: edit-own-profile
     *
     * @see AlumniProfileController::completeProfile()
     */
    Route::post('alumni/me/complete-profile', [AlumniProfileController::class, 'completeProfile'])
        ->middleware('permission:edit-own-profile')
        ->name('alumni.me.complete-profile');

    /**
     * Retrieve a specific alumni profile by ID.
     *
     * Endpoint:
     * - GET /api/v1/alumni/{alumni}
     *
     * Notes:
     * - This dynamic route is placed last to avoid conflicts with static routes (e.g., /alumni/me).
     * - {alumni} is resolved via route model binding.
     *
     * Authorization:
     * - permission: view-alumni-profiles
     *
     * @see AlumniProfileController::show()
     */
    Route::get('alumni/{alumni}', [AlumniProfileController::class, 'show'])
        ->middleware('permission:view-alumni-profiles')
        ->name('alumni.show');

    /**
     * Complete the authenticated alumni user's profile.
     *
     * Endpoint:
     * - POST /api/v1/alumni/me/complete-profile
     *
     * @see AlumniProfileController::completeProfile()
     */
    Route::post('alumni/me/complete-profile', [AlumniProfileController::class, 'completeProfile'])
        ->withoutMiddleware([EnsureTokenIsFullAccess::class])
        ->name('alumni.me.complete-profile');
});
