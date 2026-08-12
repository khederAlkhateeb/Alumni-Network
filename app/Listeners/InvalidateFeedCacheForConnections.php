<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Events\PostDeleted;
use App\Events\PostUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidates the cached feed for a post author's connections (and the
 * author themselves) whenever a post is created, updated, or deleted,
 * so their next feed request reflects the change immediately instead
 * of waiting out the 5-minute cache TTL.
 *
 * Implements ShouldQueue so this runs asynchronously — looping over
 * potentially hundreds of connections must never block the HTTP
 * response for the person who triggered the change.
 */
class InvalidateFeedCacheForConnections implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * PostCreated/PostUpdated carry the full Post model (safe to use,
     * since the post still exists in the database when the queued
     * listener runs). PostDeleted deliberately carries only primitive
     * IDs instead — see PostDeleted's docblock for why the model itself
     * must never be touched there.
     */
    public function handle(PostCreated|PostUpdated|PostDeleted $event): void
    {
        if ($event instanceof PostDeleted) {
            $authorId = $event->authorId;
            $connectedUserIds = \App\Models\User::find($authorId)?->connectedUserIds() ?? [];
        } else {
            $post = $event->post;
            $authorId = $post->user_id;
            $connectedUserIds = $post->user->connectedUserIds();
        }

        Cache::forget("feed_user_{$authorId}");

        foreach ($connectedUserIds as $userId) {
            Cache::forget("feed_user_{$userId}");
        }
    }
}
