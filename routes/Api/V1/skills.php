<?php


use App\Http\Controllers\Api\V1\AlumniSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('alumni/me/skills')->name('alumni.me.skills.')->group(function () {
    Route::post('/', [AlumniSkillController::class, 'store'])->name('store');
    Route::delete('/{skill}', [AlumniSkillController::class, 'destroy'])->name('destroy');
});
