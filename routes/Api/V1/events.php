<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EventController;

/*
|--------------------------------------------------------------------------
| University Events routes
|--------------------------------------------------------------------------
|
| All endpoints are scoped to a specific university and require a valid
| Sanctum access token (guard "api"). The "event" binding is scoped to
| "university" so a mismatched event/university pair returns 404
| instead of leaking data across universities.
|
| Endpoints are split into two groups:
| - Authenticated user endpoints: any active, logged-in user.
| - University Admin endpoints: additionally require the "uni_admin" role.
|
*/

Route::middleware(['auth:sanctum'])
    ->prefix('universities/{university}')
    ->scopeBindings()
    ->group(function () {

        /*
        |----------------------------------------------------------------------
        | Authenticated user endpoints
        |----------------------------------------------------------------------
        */

        /**
         * List all events for the given university.
         * @see EventController::index()
         */
        Route::get('/events', [EventController::class, 'index'])->name('api.events.index');

        /**
         * Show a single event's details.
         * @see EventController::show()
         */
        Route::get('/events/{event}', [EventController::class, 'show'])->name('api.events.show');

        /**
         * Register the authenticated user for an event.
         * @see EventController::register()
         */
        Route::post('/events/{event}/register', [EventController::class, 'register'])->name('api.events.register');

        /**
         * Cancel the authenticated user's registration for an event.
         * @see EventController::cancelRegistration()
         */
        Route::delete('/events/{event}/register', [EventController::class, 'cancelRegistration'])->name('api.events.cancel-registration');

        /*
        |----------------------------------------------------------------------
        | University Admin endpoints (role: uni_admin)
        |----------------------------------------------------------------------
        */
        Route::middleware(['role:uni_admin'])->group(function () {

            /**
             * Create a new event for the university.
             * @see EventController::store()
             */
            Route::post('/events', [EventController::class, 'store'])->name('api.events.store');

            /**
             * Update an existing event.
             * @see EventController::update()
             */
            Route::put('/events/{event}', [EventController::class, 'update'])->name('api.events.update');

            /**
             * List all registrations for a specific event.
             * @see EventController::registrations()
             */
            Route::get('/events/{event}/registrations', [EventController::class, 'registrations'])->name('api.events.registrations');

            /**
             * Mark attendance for a user at an event.
             * @see EventController::attend()
             */
            Route::post('/events/{event}/attend', [EventController::class, 'attend'])->name('api.events.attend');
        });
    });
