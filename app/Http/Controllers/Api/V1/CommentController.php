<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\CreateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\V1\Actions\Comment\CreateCommentAction;
use App\V1\Actions\Comment\DeleteCommentAction;
use App\V1\Actions\Comment\GetCommentsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

/**
 * Class CommentController
 *
 * Handles HTTP requests related to post comments:
 * listing, viewing, creating, and deleting comments (including nested replies).
 *
 * @package App\Http\Controllers\Api\V1
 */
class CommentController extends Controller
{
    /**
     * CommentController constructor.
     *
     * Injects the required action classes to handle business logic.
     *
     * @param CreateCommentAction $createCommentAction Handles comment creation.
     * @param GetCommentsAction   $getCommentsAction   Handles paginated comment retrieval for a post.
     * @param DeleteCommentAction $deleteCommentAction Handles comment deletion.
     */
    public function __construct(
        private readonly CreateCommentAction $createCommentAction,
        private readonly GetCommentsAction $getCommentsAction,
        private readonly DeleteCommentAction $deleteCommentAction,
    ) {}

    /**
     * List paginated top-level comments (with their replies) for a given post.
     *
     * @method GET /posts/{post}/comments
     *
     * @param Post $post The post model instance to list comments for.
     *
     * @return JsonResponse Returns a JSON response containing the paginated comments.
     */
    public function index(Post $post)
    {
        $comments = $this->getCommentsAction->handle($post);
        $paginatedData = $comments->toArray();
        return $this->successResponse(
            data: $paginatedData['data'],
            message: 'Comments retrieved successfully',
            code: 200,
            meta: Arr::except($paginatedData, ['data'])
        );
    }

    /**
     * Create a new comment (or reply) on a post.
     *
     * The comment author is always the currently authenticated user —
     * never taken from the URL/route, to avoid impersonation.
     *
     * @method POST /posts/{post}/comments
     *
     * @param CreateCommentRequest $request Validated request data (content, optional parent_comment_id).
     * @param Post                 $post    The post being commented on.
     *
     * @return JsonResponse Returns a JSON response containing the newly created comment.
     */
    public function store(CreateCommentRequest $request, Post $post)
    {
        $this->authorize('create', Comment::class);
        $comment = $this->createCommentAction->handle(
            $request->user(),
            $post,
            $request->validated()
        );

        return $this->successResponse(
            data: $comment,
            message: 'Comment created successfully',
            code: 201
        );
    }

    /**
     * Remove the specified comment from storage.
     *
     * @method DELETE /comments/{comment}
     *
     * @param Comment $comment The comment model instance to be deleted.
     *
     * @return JsonResponse Returns a JSON response confirming the successful deletion.
     */
    public function delete(Post $post, Comment $comment)
    {
        $this->authorize('delete', $comment);
        $this->deleteCommentAction->handle($comment);
        return $this->successResponse(
            message: 'Comment deleted successfully',
            code: 200
        );
    }
}
