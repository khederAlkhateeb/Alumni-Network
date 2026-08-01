<?php

use App\Http\Controllers\Api\V1\WorkExperienceController;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Alumni Work Experience Routes
 * --------------------------------------------------------------------------
 *
 * These routes allow authenticated alumni users to manage their work
 * experience entries. All routes are protected by Sanctum authentication
 * and require the user to have the "alumni" role.
 *
 * Base Prefix:
 * - /api/v1/alumni/me/work-experiences
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\WorkExperienceController
 *
 * Features:
 * - Add new work experience
 * - Update existing work experience
 * - Delete work experience
 *
 * Authorization:
 * - All operations are authorized through WorkExperiencePolicy.
 */
Route::prefix('alumni/me/work-experiences')
    ->middleware(['auth:sanctum', 'role:alumni'])
    ->name('alumni.me.work-experiences.')
    ->group(function () {

        /**
         * Create a new work experience entry for the authenticated alumni user.
         *
         * Endpoint:
         * - POST /api/v1/alumni/me/work-experiences
         *
         * @see WorkExperienceController::store()
         */
        Route::post('/', [WorkExperienceController::class, 'store'])
            ->name('store');

        /**
         * Update an existing work experience entry.
         *
         * Endpoint:
         * - PUT /api/v1/alumni/me/work-experiences/{workExperience}
         *
         * Notes:
         * - {workExperience} is resolved via route model binding.
         *
         * @see WorkExperienceController::update()
         */
        Route::put('{workExperience}', [WorkExperienceController::class, 'update'])
            ->name('update');

        /**
         * Delete a work experience entry.
         *
         * Endpoint:
         * - DELETE /api/v1/alumni/me/work-experiences/{workExperience}
         *
         * @see WorkExperienceController::destroy()
         */
        Route::delete('{workExperience}', [WorkExperienceController::class, 'destroy'])
            ->name('destroy');
    });
