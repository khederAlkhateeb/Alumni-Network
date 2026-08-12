<?php

use App\Http\Controllers\Api\V1\MentorshipRequestController;
use App\Http\Middleware\EnsureProfileIsActive;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mentorship Routes
|--------------------------------------------------------------------------
|
| These endpoints manage mentorship sessions, requests, and mentor listings.
| Protected by Sanctum authentication and mandatory active profile checks.
|
*/
Route::middleware(['auth:sanctum', EnsureProfileIsActive::class])->group(function () {

    /**
     * Get a list of all available mentors.
     * @see MentorshipRequestController::availableMentors()
     */
    Route::get('/mentors', [MentorshipRequestController::class, 'availableMentors'])
        ->middleware('permission:view-available-mentors')
        ->name('mentors.index');

    Route::prefix('mentorship-requests')
        ->name('mentorship-requests.')
        ->group(function () {

            /**
             * Get incoming mentorship requests for the authenticated mentor.
             * @see MentorshipRequestController::incoming()
             */
            Route::get('/incoming', [MentorshipRequestController::class, 'incoming'])
                ->middleware('permission:accept-mentorship-request')
                ->name('incoming');

            /**
             * Get outgoing mentorship requests sent by the authenticated mentee.
             * @see MentorshipRequestController::outgoing()
             */
            Route::get('/outgoing', [MentorshipRequestController::class, 'outgoing'])
                ->middleware('permission:send-mentorship-request')
                ->name('outgoing');

            /**
             * Store a new mentorship request.
             * @see MentorshipRequestController::store()
             */
            Route::post('/', [MentorshipRequestController::class, 'store'])
                ->middleware('permission:send-mentorship-request')
                ->name('store');

            /**
             * Accept a specific mentorship request.
             * @see MentorshipRequestController::accept()
             */
            Route::post('/{mentorshipRequest}/accept', [MentorshipRequestController::class, 'accept'])
                ->middleware('permission:accept-mentorship-request')
                ->name('accept');

            /**
             * Reject a specific mentorship request.
             * @see MentorshipRequestController::reject()
             */
            Route::post('/{mentorshipRequest}/reject', [MentorshipRequestController::class, 'reject'])
                ->middleware('permission:reject-mentorship-request')
                ->name('reject');

            /**
             * Mark a specific mentorship request as complete.
             * @see MentorshipRequestController::complete()
             */
            Route::post('/{mentorshipRequest}/complete', [MentorshipRequestController::class, 'complete'])
                ->middleware('permission:complete-mentorship')
                ->name('complete');
        });
});
