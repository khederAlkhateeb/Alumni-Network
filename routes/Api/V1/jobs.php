<?php

use App\Http\Controllers\Api\V1\JobListingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/jobs', [JobListingController::class, 'index']);
    Route::post('/jobs', [JobListingController::class, 'store']);
    Route::get('/jobs/my-applications', [JobListingController::class, 'myApplications']);
    Route::get('/jobs/{jobListing}', [JobListingController::class, 'show']);
    Route::put('/jobs/{jobListing}', [JobListingController::class, 'update']);
    Route::delete('/jobs/{jobListing}', [JobListingController::class, 'destroy']);
    Route::patch('/jobs/{jobListing}/close', [JobListingController::class, 'close']);
    Route::post('/jobs/{jobListing}/apply', [JobListingController::class, 'apply']);
    Route::get('/jobs/{jobListing}/applications', [JobListingController::class, 'applications']);
    Route::patch('/jobs/{jobListing}/applications/{application}/status', [JobListingController::class, 'updateApplicationStatus']);
});
