<?php

use App\Http\Controllers\Api\V1\UniversityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| University routes
|--------------------------------------------------------------------------
|
| Index is public; all other endpoints require authentication.
|
*/

// get all Universities Public
Route::get('/universities', [UniversityController::class, 'index'])->name('api.v1.universities.index');

//  create , get , edit  require authentication.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/universities', [UniversityController::class, 'store'])->name('api.v1.universities.store');
    Route::get('/universities/{university}', [UniversityController::class, 'show'])->name('api.v1.universities.show');
    Route::put('/universities/{university}', [UniversityController::class, 'update'])->name('api.v1.universities.update');
    Route::delete('/universities/{university}', [UniversityController::class, 'destroy'])->name('api.v1.universities.destroy');
});