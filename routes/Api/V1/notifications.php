<?php

use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Notifications Management routes
|--------------------------------------------------------------------------
|
| All endpoints operate strictly on the authenticated user's own
| notifications (database channel). There is no cross-user access,
| so no Policy is required — the query is always scoped through
| $request->user()->notifications().
|
*/

Route::middleware(['auth:sanctum'])
    ->prefix('notifications')
    ->group(function () {

        /**
         * List the authenticated user's notifications (paginated).
         * @see NotificationController::index()
         */
        Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');

        /**
         * Mark all of the authenticated user's unread notifications as read.
         * @see NotificationController::markAllAsRead()
         */
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.read-all');

        /**
         * Mark a single notification as read.
         * @see NotificationController::markAsRead()
         */
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
    });
