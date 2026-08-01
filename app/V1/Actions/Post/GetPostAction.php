<?php

namespace App\V1\Actions\Post;

use App\Models\Post;

/**
 * Handles retrieving a single post along with its
 * commonly-needed relations.
 */
class GetPostAction
{
    /**
     * Fetch a single post with its relations loaded.
     *
     * Authorization (e.g. visibility rules: public / connections /
     * university) is handled separately via PostPolicy — this action
     * assumes the caller already passed that check.
     *
     * @param Post $post The post to retrieve (typically resolved via Route Model Binding).
     *
     * @return Post The post with 'user', 'comments.user', and 'reactions' loaded.
     */
    public function handle(Post $post): Post
    {
        return $post->load([
            'user',
            'comments' => fn ($query) => $query->whereNull('parent_comment_id')->latest(),
            'comments.user',
            'comments.replies.user',
            'reactions',
        ]);
    }
}
