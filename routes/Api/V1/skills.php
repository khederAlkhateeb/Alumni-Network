<?php

use App\Http\Controllers\Api\V1\AlumniSkillController;
use App\Http\Middleware\EnsureProfileIsActive;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Alumni Skill Management Routes
 * --------------------------------------------------------------------------
 *
 * Base Prefix:
 * - /api/v1/alumni/me/skills
 *
 * Base Middleware:
 * - auth:sanctum
 * - role:alumni
 * - EnsureProfileIsActive
 * - permission:manage-skills
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\AlumniSkillController
 */
Route::middleware([
        'auth:sanctum',
        'role:alumni',
        EnsureProfileIsActive::class,
        'permission:manage-skills',
    ])
    ->prefix('alumni/me/skills')
    ->name('alumni.me.skills.')
    ->group(function () {

        /**
         * Add a new skill to the authenticated alumni user's profile.
         *
         * Endpoint:
         * - POST /api/v1/alumni/me/skills
         *
         * @see AlumniSkillController::store()
         */
        Route::post('/', [AlumniSkillController::class, 'store'])
            ->name('store');

        /**
         * Delete a skill from the authenticated alumni user's profile.
         *
         * Endpoint:
         * - DELETE /api/v1/alumni/me/skills/{skill}
         *
         * Notes:
         * - {skill} is resolved via route model binding.
         *
         * @see AlumniSkillController::destroy()
         */
        Route::delete('/{skill}', [AlumniSkillController::class, 'destroy'])
            ->name('destroy');
    });
