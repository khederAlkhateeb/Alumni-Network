<?php

use App\Http\Controllers\Api\V1\ReactController;

Route::middleware('auth:api')->group(function () {
    Route::post('/posts/{post}/react', [ReactController::class, 'store'])->name('reacts.store');
});
