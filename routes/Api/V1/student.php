<?php

use App\Http\Controllers\Api\V1\StudentProfileController;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Student Profile Routes
 * --------------------------------------------------------------------------
 *
 * These routes allow authenticated student users to view and update
 * their own profile, as well as view other students' public profiles.
 *
 * Base Prefix:
 * - /api/v1/students
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\StudentProfileController
 *
 * Features:
 * - View own student profile
 * - Update own student profile
 * - View public student profiles
 *
 * Authorization:
 * - All operations require Sanctum authentication.
 * - Viewing other students' profiles is allowed for any authenticated user.
 */
Route::middleware('auth:sanctum')->group(function () {

    /**
     * Get the authenticated student's profile.
     *
     * Endpoint:
     * - GET /api/v1/students/me
     *
     * @see StudentProfileController::showMe()
     */
    Route::get('/students/me', [StudentProfileController::class, 'showMe']);

    /**
     * Update the authenticated student's profile.
     *
     * Endpoint:
     * - PUT /api/v1/students/me
     *
     * @see StudentProfileController::updateMe()
     */
    Route::put('/students/me', [StudentProfileController::class, 'updateMe']);

    /**
     * View another student's public profile.
     *
     * Endpoint:
     * - GET /api/v1/students/{student}
     *
     * Notes:
     * - {student} is resolved via route model binding.
     *
     * @see StudentProfileController::show()
     */
    Route::get('/students/{student}', [StudentProfileController::class, 'show']);
});
