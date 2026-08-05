<?php

namespace App\V1\Actions\MentorshipRequest;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Class GetAvailableMentorsAction
 *
 * Handles retrieving a paginated list of available mentors
 * whose profiles are open for mentorship, utilizing cache tags for performance.
 *
 * @package App\V1\Actions\MentorshipRequest
 */
class GetAvailableMentorsAction
{
    /**
     * Execute the action to fetch available mentors with pagination.
     *
     * @param  int  $page
     * @param  int|null  $perPage
     * @returnLengthAwarePaginator
     */
    public function handle(int $page = 1, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('app.pagination.per_page', 15);

        $cacheKey = "available_mentors_ids";

        $mentorIds = Cache::tags(['available_mentors'])
            ->remember($cacheKey, now()->addMinutes(15),function () {
                return User::query()
                    ->whereHas('alumniProfile', fn($q) => $q->where('is_open_to_mentor', true))
                    ->pluck('id')
                    ->toArray();
            });

        return User::whereIn('id', $mentorIds)
            ->latest()
            ->paginate($perPage);
    }
}
