<?php

namespace App\V1\Actions\Comment;

use App\Models\Comment;

/**
 * Class DeleteCommentAction
 *
 * Handles the logic for deleting a specific comment.
 * If the database migration is set up with 'ON DELETE CASCADE' for parent_comment_id,
 * all associated replies will be automatically deleted by the database.
 *
 * @package App\V1\Actions\Comment
 */
class DeleteCommentAction
{
    /**
     * Execute the action to delete the given comment.
     *
     * @param Comment $comment The comment model instance to be deleted.
     *
     * @return bool|null Returns true if the deletion was successful, or null/false otherwise.
     */
    public function handle(Comment $comment): ?bool
    {
        return $comment->delete();
    }
}
