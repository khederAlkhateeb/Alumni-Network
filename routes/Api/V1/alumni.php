<?php

use App\Http\Controllers\Api\V1\AlumniProfileController;
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
 * - Upload or delete profile photos
 * - View other alumni profiles
 *
 * Authorization:
 * - All routes require:
 *      - Valid Sanctum token
 *      - User role: alumni
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\AlumniProfileController
 */
Route::middleware(['auth:sanctum', 'role:alumni'])->group(function () {

    /**
     * Retrieve a list of alumni profiles.
     *
     * Endpoint:
     * - GET /api/v1/alumni
     *
     * @see AlumniProfileController::index()
     */
    Route::get('alumni', [AlumniProfileController::class, 'index'])
        ->name('alumni.index');

    /**
     * Retrieve the authenticated alumni user's own profile.
     *
     * Endpoint:
     * - GET /api/v1/alumni/me
     *
     * @see AlumniProfileController::showMe()
     */
    Route::get('alumni/me', [AlumniProfileController::class, 'showMe']);

    /**
     * Update the authenticated alumni user's profile.
     *
     * Endpoint:
     * - PUT /api/v1/alumni/me/updateMe
     *
     * @see AlumniProfileController::updateMe()
     */
    Route::put('alumni/me/updateMe', [AlumniProfileController::class, 'updateMe']);

    /**
     * Toggle mentor status for the authenticated alumni user.
     *
     * Endpoint:
     * - POST /api/v1/alumni/me/toggle-mentor
     *
     * @see AlumniProfileController::toggleMentor()
     */
    Route::post('alumni/me/toggle-mentor', [AlumniProfileController::class, 'toggleMentor'])
        ->name('alumni.me.toggle-mentor');

    /**
     * Upload a profile photo for the authenticated alumni user.
     *
     * Endpoint:
     * - POST /api/v1/alumni/me/photo
     *
     * @see AlumniProfileController::uploadPhoto()
     */
    Route::post('alumni/me/photo', [AlumniProfileController::class, 'uploadPhoto'])
        ->name('alumni.me.photo.upload');

    /**
     * Delete the authenticated alumni user's profile photo.
     *
     * Endpoint:
     * - DELETE /api/v1/alumni/me/photo
     *
     * @see AlumniProfileController::destroyPhoto()
     */
    Route::delete('alumni/me/photo', [AlumniProfileController::class, 'destroyPhoto'])
        ->name('alumni.me.photo.destroy');

    /**
     * Retrieve a specific alumni profile by ID.
     *
     * Endpoint:
     * - GET /api/v1/alumni/{alumni}
     *
     * Notes:
     * - This dynamic route is placed last to avoid conflicts with static routes.
     *
     * @see AlumniProfileController::show()
     */
    Route::get('alumni/{alumni}', [AlumniProfileController::class, 'show'])
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
        ->name('alumni.me.complete-profile');
});
