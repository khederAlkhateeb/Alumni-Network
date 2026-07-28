<?php

use App\Http\Controllers\Api\V1\WorkExperienceController;
use Illuminate\Support\Facades\Route;

Route::prefix('alumni/me/work-experiences')->name('alumni.me.work-experiences.')->group(function () {
    Route::post('/', [WorkExperienceController::class, 'store'])->name('store');
    Route::put('{workExperience}', [WorkExperienceController::class, 'update'])->name('update');
    Route::delete('{workExperience}', [WorkExperienceController::class, 'destroy'])->name('destroy');
});
