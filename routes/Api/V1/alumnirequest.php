<?php

use App\Http\Controllers\Api\V1\GraduationRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1 Graduation & Alumni Requests
|--------------------------------------------------------------------------
|
| These endpoints handle graduation and alumni requests for students
| and university administrators.
|
*/

/*
|--------------------------------------------------------------------------
| Student graduation request routes
|--------------------------------------------------------------------------
|
| These endpoints require a valid Sanctum access token and the "student" role.
|
*/
Route::middleware(['auth:sanctum', 'role:student'])->prefix('student')->group(function () {
    /**
     * Submit a new graduation request.
     * @see GraduationRequestController::store()
     */
    Route::post('/graduation-requests', [GraduationRequestController::class, 'store'])->name('api.v1.student.graduation-requests.store');
});

/*
|--------------------------------------------------------------------------
| University Admin graduation request management routes
|--------------------------------------------------------------------------
|
| These endpoints require a valid Sanctum access token and the "uni_admin" role.
|
*/
Route::middleware(['auth:sanctum', 'role:uni_admin'])->prefix('admin')->group(function () {
    /**
     * Approve a specific graduation request.
     * @see GraduationRequestController::approve()
     */
    Route::patch('/graduation-requests/{graduationRequest}/approve', [GraduationRequestController::class, 'approve'])->name('api.v1.admin.graduation-requests.approve');

    /**
     * Reject a specific graduation request.
     * @see GraduationRequestController::reject()
     */
    Route::patch('/graduation-requests/{graduationRequest}/reject', [GraduationRequestController::class, 'reject'])->name('api.v1.admin.graduation-requests.reject');

    /**
     * Retrieve a list of graduation requests.
     * @see GraduationRequestController::index()
     */
    Route::get('/graduation-requests', [GraduationRequestController::class, 'index'])->name('api.v1.admin.graduation-requests.index');
});
