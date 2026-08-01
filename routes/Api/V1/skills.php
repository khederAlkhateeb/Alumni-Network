<?php

use App\Http\Controllers\Api\V1\AlumniSkillController;
use Illuminate\Support\Facades\Route;

/**
 * --------------------------------------------------------------------------
 * Alumni Skill Management Routes
 * --------------------------------------------------------------------------
 *
 * These routes allow authenticated alumni users to manage the list of skills
 * associated with their profile. All routes are protected by Sanctum
 * authentication and require the user to have the "alumni" role.
 *
 * Base Prefix:
 * - /api/v1/alumni/me/skills
 *
 * Controller:
 * - App\Http\Controllers\Api\V1\AlumniSkillController
 *
 * Features:
 * - Add a new skill to the authenticated alumni user's profile.
 * - Remove an existing skill from the authenticated alumni user's profile.
 *
 * Authorization:
 * - All operations are authorized through AlumniSkillPolicy.
 */
Route::middleware(['auth:sanctum','role:alumni'])
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
