<?php

use App\Http\Controllers\Api\V1\WorkExperienceController;
use App\Http\Middleware\EnsureProfileIsActive;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Alumni Work Experience Routes
 * --------------------------------------------------------------------------
 *
 * Base Prefix:
 * - /api/v1/alumni/me/work-experiences
 *
 * Base Middleware:
 * - auth:sanctum
 * - role:alumni
 * - EnsureProfileIsActive
 * - permission:manage-work-experiences
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\WorkExperienceController
 */
Route::prefix('alumni/me/work-experiences')
    ->middleware([
        'auth:sanctum',
        'role:alumni',
        EnsureProfileIsActive::class,
        'permission:manage-work-experiences',
    ])
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
