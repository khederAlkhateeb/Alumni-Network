<?php

use App\Http\Controllers\Api\V1\StudentProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {


    Route::get('/students/me', [StudentProfileController::class, 'showMe']);
    Route::put('/students/me', [StudentProfileController::class, 'updateMe']);

  
    Route::get('/students/{student}', [StudentProfileController::class, 'show']);

});
