<?php

use App\Http\Controllers\Api\V1\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Send a message (creates the conversation implicitly if needed)
    Route::post('/messages', [MessageController::class, 'store']);

    // List messages within a specific conversation
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'show']);
    // list conversations
    Route::get('/messages', [MessageController::class, 'index']);

// mark the massage read-at
    Route::post('/messages/{uid}/read', [MessageController::class, 'markAsRead']);
});

