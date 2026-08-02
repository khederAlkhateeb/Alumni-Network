<?php

use App\Http\Controllers\Api\V1\ConnectionContoller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Users Connection routes
|--------------------------------------------------------------------------
|
| These endpoints require a valid Sanctum access token. The token is
| passed via the "Authorization: Bearer {token}" header.
|
| A connection always has a "requester" (the user who sends the request)
| and a "receiver" (the user who receives it). The connection lifecycle
| is: pending -> accepted, or pending -> rejected. Blocking and removal
| are managed through dedicated statuses/endpoints.
|
*/
Route::middleware(['auth:sanctum'])->group(function () {
    /**
     * List the authenticated user's connections (accepted + outgoing).
     * @see ConnectionContoller::index()
     */
    Route::get('/connections', [ConnectionContoller::class, 'index'])->name('api.v1.connections.index');

    /**
     * List the pending connection requests received by the authenticated user.
     * @see ConnectionContoller::pending()
     */
    Route::get('/connections/pending', [ConnectionContoller::class, 'pending'])->name('api.v1.connections.pending');

    /**
     * Send a connection request to the given user.
     * Fails (422) if the target is the authenticated user, has blocked
     * the user, or rejected a previous request within the cooldown period.
     * @see ConnectionContoller::store()
     */
    Route::post('/connections/{user}', [ConnectionContoller::class, 'store'])->name('api.v1.connections.store');

    /**
     * Accept a pending connection request received by the authenticated user.
     * @see ConnectionContoller::accepte()
     */
    Route::post('/connections/{connection}/accept', [ConnectionContoller::class, 'accepte'])->name('api.v1.connections.accept');

    /**
     * Reject a pending connection request received by the authenticated user.
     * @see ConnectionContoller::reject()
     */
    Route::post('/connections/{connection}/reject', [ConnectionContoller::class, 'reject'])->name('api.v1.connections.reject');

    /**
     * Remove an accepted connection involving the authenticated user.
     * @see ConnectionContoller::destroy()
     */
    Route::delete('/connections/{connection}', [ConnectionContoller::class, 'destroy'])->name('api.v1.connections.destroy');


    /**
     * Block an accepted connection .
     * @see ConnectionContoller::block()
     */
    Route::post('/connections/{connection}/block', [ConnectionContoller::class, 'block'])->name('api.v1.connections.block');

});
