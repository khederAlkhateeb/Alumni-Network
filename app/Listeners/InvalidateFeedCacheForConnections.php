<?php

namespace App\Listeners;

use App\Enums\ConnectionStatus;
use App\Events\PostCreated;
use App\Models\Connection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidates the cached feed for the post author's connections
 * (and the author themselves) whenever a new post is created,
 * so their next feed request reflects the new post immediately
 * instead of waiting out the 5-minute cache TTL.
 *
 * Implements ShouldQueue so this runs asynchronously — looping
 * over potentially hundreds of connections must never block the
 * HTTP response for the person who just created the post.
 */
class InvalidateFeedCacheForConnections implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PostCreated $event): void
    {
        $post = $event->post;
        $user = $post->user;
        $authorId = $post->user_id;

        // The author sees their own new post immediately too.
        Cache::forget("feed_user_{$authorId}");

        $connectedUserIds = $user->connectedUserIds();
        foreach ($connectedUserIds as $userId) {
            Cache::forget("feed_user_{$userId}");
        }
    }
}
