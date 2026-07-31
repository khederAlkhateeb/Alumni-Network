<?php

use App\Http\Controllers\Api\V1\UniversityAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| University Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum','role:super_admin'])->group(function () {

    /**
     * CRUD operations for managing university admins.
     * @see UniversityAdminController
     */
    Route::apiResource('university-admins', UniversityAdminController::class);

});