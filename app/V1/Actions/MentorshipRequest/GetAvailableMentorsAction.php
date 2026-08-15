<?php

namespace App\V1\Actions\MentorshipRequest;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Class GetAvailableMentorsAction
 *
 * Retrieves a paginated list of available mentors with caching support.
 *
 * This action:
 * - Applies pagination manually (page + perPage).
 * - Uses cache tags to store paginated mentor lists for 15 minutes.
 * - Hydrates raw cached arrays back into Eloquent models.
 * - Ensures related mentorship request + program data is eager-loaded.
 * - Returns a LengthAwarePaginator instance identical to Laravel's paginator.
 *
 * @package App\V1\Actions\MentorshipRequest
 */
class GetAvailableMentorsAction
{
    /**
     * Fetch a paginated list of mentors with caching.
     *
     * @param int $page     The current page number (1-based).
     * @param int $perPage  Number of mentors per page.
     *
     * @return LengthAwarePaginator Paginated mentors list.
     */
    public function handle(int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        // Normalize pagination inputs
        $perPage = max(1, $perPage);
        $page = max(1, $page);

        // Cache key depends on page + perPage
        $cacheKey = "available_mentors_p{$page}_l{$perPage}";

        // Fetch from cache or compute
        $cached = Cache::tags(['available_mentors'])->remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($page, $perPage) {
                $paginator = User::query()
                    ->role('alumni')
                    ->with(['receivedMentorshipRequests.program'])
                    ->latest()
                    ->paginate($perPage, ['*'], 'page', $page);

                return [
                    'items' => $paginator->items(),
                    'total' => $paginator->total(),
                ];
            }
        );

        // Convert cached arrays back into Eloquent models
        $models = User::hydrate($cached['items'] ?? []);

        // Re-load relationships (because hydrate does not load relations)
        if ($models->isNotEmpty()) {
            $models->load(['receivedMentorshipRequests.program']);
        }

        // Return a paginator identical to Laravel's default paginator
        return new LengthAwarePaginator(
            $models,
            $cached['total'] ?? 0,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }
}
