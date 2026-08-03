<?php

use App\Http\Controllers\Api\V1\CommentController;
use Illuminate\Support\Facades\Route;
/**
 * --------------------------------------------------------------------------
 * API Routes for Post Comments (V1)
 * --------------------------------------------------------------------------
 *
 * All routes within this group are protected by the 'auth:api' middleware,
 * ensuring that only authenticated users can interact with comments.
 */
Route::middleware('auth:api')->group(function () {

    /**
     * Create a new comment or reply on a specific post.
     *
     * @method POST /posts/{post}/comments
     * @uses   \App\Http\Controllers\Api\V1\CommentController::store
     * @name   comments.store
     */
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');

    /**
     * Retrieve a paginated list of comments (and replies) for a specific post.
     *
     * @method GET /posts/{post}/comments
     * @uses   \App\Http\Controllers\Api\V1\CommentController::index
     * @name   comments.index
     */
    Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');

    /**
     * Delete a specific comment from a post.
     *
     * @method DELETE /posts/{post}/comments/{comment}
     * @uses   \App\Http\Controllers\Api\V1\CommentController::delete
     * @name   comments.delete
     */
    Route::delete('/posts/{post}/comments/{comment}', [CommentController::class, 'delete'])->name('comments.delete');

});
