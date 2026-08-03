<?php

namespace App\V1\Actions\Feed;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetFeedAction
{
    /**
     * Build the user's feed: posts from their accepted connections,
     * university announcements, and posts from other alumni at the
     * same university — cached per-user for 5 minutes (spec 5.3).
     *
     * Authorization (active/pending status) is handled separately via
     * a Policy — this action assumes the caller already passed that check.
     *
     * @param User $user The authenticated user requesting their feed.
     *
     * @return CursorPaginator Cursor-paginated posts, most recent first.
     */
    public function handle(User $user): CursorPaginator
    {
        return Cache::remember(
            "feed_user_{$user->id}",
            300,
            function () use ($user) {
                $connectionIds = $user->connectedUserIds();
                $universityId = $user->alumniProfile?->major?->faculty?->university_id;

                return Post::query()
                    ->fromConnections($connectionIds)
                    ->orWhere(fn ($q) => $q->universityAnnouncements($universityId))
                    ->orWhere(fn ($q) => $q->fromSameUniversityAlumni($universityId))
                    ->with(['user', 'reactions'])
                    ->latest()
                    ->cursorPaginate(5);
            }
        );
    }
}
