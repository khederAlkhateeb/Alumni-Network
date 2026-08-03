<?php

namespace App\V1\Actions\Comment;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class GetCommentsAction
 *
 * Handles the logic for retrieving paginated comments for a specific post.
 *
 * @package App\V1\Actions\Comment
 */
class GetCommentsAction
{
    /**
     * Retrieve a paginated list of top-level comments for the given post,
     * ordered by the most recent first, with their replies and authors
     * eager-loaded.
     *
     * Only top-level comments (parent_comment_id = null) are paginated
     * directly; their replies are loaded as a nested relation so that
     * a single comment thread with many replies doesn't crowd out other
     * top-level comments on the same page.
     *
     * @param Post $post The post model instance to get comments for.
     *
     * @return LengthAwarePaginator Returns a paginated collection of top-level comments.
     */
    public function handle(Post $post): LengthAwarePaginator
    {
        return $post->comments()
            ->whereNull('parent_comment_id')
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(15);
    }
}
