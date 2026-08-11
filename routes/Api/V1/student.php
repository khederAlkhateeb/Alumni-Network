<?php

use App\Http\Controllers\Api\V1\StudentProfileController;
use App\Http\Middleware\EnsureProfileIsActive;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Student Profile Routes
 * --------------------------------------------------------------------------
 *
 * Base Prefix:
 * - /api/v1/students
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\StudentProfileController
 */
Route::middleware(['auth:sanctum', EnsureProfileIsActive::class])->group(function () {

    /**
     * Get the authenticated student's profile.
     *
     * Endpoint:
     * - GET /api/v1/students/me
     *
     * @see StudentProfileController::showMe()
     */
    Route::get('/students/me', [StudentProfileController::class, 'showMe'])
        ->middleware('permission:view-student-profiles')
        ->name('students.me.show');

    /**
     * Update the authenticated student's profile.
     *
     * Endpoint:
     * - PUT /api/v1/students/me
     *
     * @see StudentProfileController::updateMe()
     */
    Route::put('/students/me', [StudentProfileController::class, 'updateMe'])
        ->middleware('permission:edit-own-profile')
        ->name('students.me.update');

    /**
     * View another student's public profile.
     *
     * Endpoint:
     * - GET /api/v1/students/{student}
     *
     * Notes:
     * - {student} is resolved via route model binding.
     * - Kept at the bottom to avoid routing conflicts with /students/me.
     *
     * @see StudentProfileController::show()
     */
    Route::get('/students/{student}', [StudentProfileController::class, 'show'])
        ->middleware('permission:view-student-profiles')
        ->name('students.show');
});
