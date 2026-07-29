<?php

use App\Http\Controllers\Api\V1\FacultyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::resource('faculties', FacultyController::class);
});
