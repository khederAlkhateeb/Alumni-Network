<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired right after a post is deleted.
 *
 * Deliberately carries primitive values (post/author IDs) instead of
 * the Post model itself. SerializesModels re-fetches models from the
 * database when a queued listener actually runs — but by definition,
 * the post is already gone from the database by the time this event
 * fires, so re-fetching it would throw a ModelNotFoundException. Since
 * the listener only ever needs the IDs (to invalidate cache entries),
 * there's no reason to risk touching the model at all here.
 */
class PostDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $postId,
        public readonly int $authorId,
    ) {}
}
