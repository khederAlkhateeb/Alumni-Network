<?php

namespace App\V1\Actions\Comment;

use App\Models\Comment;
use App\Models\Post;

class DeleteCommentAction
{
    public function handle(Comment $comment)
    {
        return $comment->with('replies')->delete();
    }
}
