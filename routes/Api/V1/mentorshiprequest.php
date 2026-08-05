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
    Route::get('/mentors', [MentorshipRequestController::class, 'availableMentors']);

    Route::prefix('mentorship-requests')->group(function () {
        /**
         * Get incoming mentorship requests for the authenticated mentor.
         * @see MentorshipRequestController::incoming()
         */
        Route::get('/incoming', [MentorshipRequestController::class, 'incoming']);

        /**
         * Get outgoing mentorship requests sent by the authenticated mentee.
         * @see MentorshipRequestController::outgoing()
         */
        Route::get('/outgoing', [MentorshipRequestController::class, 'outgoing']);

        /**
         * Store a new mentorship request.
         * @see MentorshipRequestController::store()
         */
        Route::post('/', [MentorshipRequestController::class, 'store']);

        /**
         * Accept a specific mentorship request.
         * @see MentorshipRequestController::accept()
         */
        Route::post('/{mentorshipRequest}/accept', [MentorshipRequestController::class, 'accept']);

        /**
         * Reject a specific mentorship request.
         * @see MentorshipRequestController::reject()
         */
        Route::post('/{mentorshipRequest}/reject', [MentorshipRequestController::class, 'reject']);

        /**
         * Mark a specific mentorship request as complete.
         * @see MentorshipRequestController::complete()
         */
        Route::post('/{mentorshipRequest}/complete', [MentorshipRequestController::class, 'complete']);
    });
});
