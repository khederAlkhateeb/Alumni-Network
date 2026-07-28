<?php

use App\Http\Controllers\Api\V1\AlumniProfileController;
use Illuminate\Support\Facades\Route;

// 1. Static routes FIRST
Route::get('alumni', [AlumniProfileController::class, 'index'])->name('alumni.index');
Route::get('alumni/me', [AlumniProfileController::class, 'showMe']);
Route::put('alumni/me/updateMe', [AlumniProfileController::class, 'updateMe']);
Route::post('alumni/me/toggle-mentor', [AlumniProfileController::class, 'toggleMentor'])->name('alumni.me.toggle-mentor');
Route::post('alumni/me/photo', [AlumniProfileController::class, 'uploadPhoto'])->name('alumni.me.photo.upload');
Route::delete('alumni/me/photo', [AlumniProfileController::class, 'destroyPhoto'])->name('alumni.me.photo.destroy');

// 2. Dynamic route LAST
Route::get('alumni/{alumni}', [AlumniProfileController::class, 'show'])->name('alumni.show');

Route::post('alumni/me/complete-profile', [AlumniProfileController::class, 'completeProfile'])
    ->name('alumni.me.complete-profile');
