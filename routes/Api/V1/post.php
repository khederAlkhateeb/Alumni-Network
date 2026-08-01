<?php

use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route; // Note: Usually Facades\Route is used in route files

/**
 * --------------------------------------------------------------------------
 * API Routes for Post Management (V1)
 * --------------------------------------------------------------------------
 *
 * This section contains the route definitions for creating, retrieving,
 * updating, and deleting posts.
 */

/**
 * Post Resource Routes
 *
 * All routes within this group are protected by the 'auth:api' middleware,
 * meaning they require a valid authentication token to be accessed.
 */
Route::middleware('auth:api')->group(function () {

    /**
     * Create a new post.
     *
     * @method POST /posts
     * @uses   \App\Http\Controllers\Api\V1\PostController::store
     * @name   posts.store
     */

    Route::post('/posts', [PostController::class, 'store'])->name('posts.store')
        ->middleware('EnsureProfileIsActive');

    /**
     * Retrieve a specific post by its model binding.
     *
     * @method GET /posts/{post}
     * @uses   \App\Http\Controllers\Api\V1\PostController::show
     * @name   posts.show
     */
    Route::get('/posts/{post}', [PostController::class, 'show'])->name('posts.show');

    /**
     * Update a specific post.
     *
     * @method PUT /posts/{post}
     * @uses   \App\Http\Controllers\Api\V1\PostController::update
     * @name   posts.update
     */
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('posts.update');

    /**
     * Delete a specific post.
     *
     * @method DELETE /posts/{post}
     * @uses   \App\Http\Controllers\Api\V1\PostController::delete
     * @name   posts.delete
     */
    Route::delete('/posts/{post}', [PostController::class, 'delete'])->name('posts.delete');

});
