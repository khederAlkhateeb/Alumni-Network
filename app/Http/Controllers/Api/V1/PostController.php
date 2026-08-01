<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\CreatePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Post;
use App\Models\User;
use App\V1\Actions\Post\CreatePostAction;
use App\V1\Actions\Post\DeletePostAction;
use App\V1\Actions\Post\GetPostAction;
use App\V1\Actions\Post\UpdatePostAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

/**
 * Class PostController
 *
 * Handles API requests for managing and interacting with Post resources.
 *
 * @package App\Http\Controllers\Api\V1
 */
class PostController extends Controller
{
    /**
     * PostController constructor.
     *
     * Injects the required action classes to handle business logic.
     *
     * @param CreatePostAction $createPostAction Handles the creation of a new post.
     * @param UpdatePostAction $updatePostAction Handles updating an existing post.
     * @param GetPostAction    $getPostAction    Handles retrieving post details.
     * @param DeletePostAction $deletePostAction Handles the deletion of a post.
     */
    public function __construct(
        private readonly CreatePostAction $createPostAction,
        private readonly UpdatePostAction $updatePostAction,
        private readonly GetPostAction $getPostAction,
        private readonly DeletePostAction $deletePostAction
    ) {}

    /**
     * Store a newly created post in storage.
     *
     * @param User              $user    The user attempting to create the post.
     * @param CreatePostRequest $request The HTTP request containing the validated post data.
     *
     * @return JsonResponse Returns a JSON response containing the newly created post data.
     *
     * @throws AuthorizationException If the current user is not authorized to create a post.
     */
    public function store(User $user, CreatePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $data = $request->validated();
        $post = $this->createPostAction->handle($user, $data);

        return $this->successResponse(
            data: $post,
            message: 'Post created successfully',
            code: 201
        );
    }

    /**
     * Update the specified post in storage.
     *
     * @param User              $user    The user attempting to update the post.
     * @param Post              $post    The post instance to be updated.
     * @param UpdatePostRequest $request The HTTP request containing the validated update data.
     *
     * @return JsonResponse Returns a JSON response containing the updated post data.
     *
     * @throws AuthorizationException If the current user is not authorized to update the specific post.
     */
    public function update(User $user, Post $post, UpdatePostRequest $request)
    {
        $this->authorize('update', $post);

        $data = $request->validated();
        $post = $this->updatePostAction->handle($user, $post, $data);

        return $this->successResponse(
            data: $post,
            message: 'Post updated successfully',
            code: 200
        );
    }

    /**
     * Display the specified post.
     *
     * @param Post $post The post instance to retrieve.
     *
     * @return JsonResponse Returns a JSON response containing the requested post data.
     *
     * @throws AuthorizationException If the current user is not authorized to view the specific post.
     */
    public function show(Post $post)
    {
        $this->authorize('view', $post);

        return $this->successResponse(
            data: $post,
            message: 'Post retrieved successfully',
            code: 200
        );
    }

    /**
     * Remove the specified post from storage.
     *
     * @param Post $post The post instance to be deleted.
     *
     * @return JsonResponse Returns a JSON response confirming the successful deletion.
     *
     * @throws AuthorizationException If the current user is not authorized to delete the specific post.
     */
    public function delete(Post $post)
    {
        $this->authorize('delete', $post);
        $this->deletePostAction->handle($post) ;
        return $this->successResponse(
            message: 'Post deleted successfully',
            code: 200
        );
    }
}
