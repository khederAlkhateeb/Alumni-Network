<?php

namespace App\V1\Actions\Comment;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Class CreateCommentAction
 *
 * Handles the business logic for creating a new comment on a post.
 * This action supports creating both top-level comments and replies to existing comments.
 *
 * @package App\V1\Actions\Post
 */
class CreateCommentAction
{
    /**
     * Execute the action to create and store a new comment.
     *
     * @param User  $user The user who is authoring the comment.
     * @param Post  $post The post that the comment is being attached to.
     * @param array $data The validated comment data.
     *                    Expected keys:
     *                    - string 'content': The actual text content of the comment.
     *                    - int|null 'parent_comment_id': The ID of the parent comment if this is a reply (optional).
     *
     * @return Comment Returns the newly created Comment model instance.
     */
    public function handle(User $user, Post $post, array $data): Comment
    {
        return Comment::create([
            'user_id'           => $user->id,
            'content'           => $data['content'],
            'post_id'           => $post->id,
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
        ]);
    }
}
