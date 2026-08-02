<?php

use App\Http\Controllers\Api\V1\JobListingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/jobs', [JobListingController::class, 'index'])
        ->middleware('permission:view-jobs');

    Route::post('/jobs', [JobListingController::class, 'store'])
        ->middleware('permission:create-job');

    Route::get('/jobs/my-applications', [JobListingController::class, 'myApplications'])
        ->middleware('permission:apply-for-job');

    Route::get('/jobs/{jobListing}', [JobListingController::class, 'show'])
        ->middleware('permission:view-jobs');

    Route::put('/jobs/{jobListing}', [JobListingController::class, 'update'])
        ->middleware('permission:edit-own-job');

    Route::delete('/jobs/{jobListing}', [JobListingController::class, 'destroy'])
        ->middleware('permission:delete-own-job|delete-any-job');

    Route::patch('/jobs/{jobListing}/close', [JobListingController::class, 'close'])
        ->middleware('permission:close-job');

    Route::post('/jobs/{jobListing}/apply', [JobListingController::class, 'apply'])
        ->middleware('permission:apply-for-job');

    Route::get('/jobs/{jobListing}/applications', [JobListingController::class, 'applications'])
        ->middleware('permission:view-job-applications');

    Route::patch('/jobs/{jobListing}/applications/{application}/status', [JobListingController::class, 'updateApplicationStatus'])
        ->middleware('permission:update-application-status');
});
