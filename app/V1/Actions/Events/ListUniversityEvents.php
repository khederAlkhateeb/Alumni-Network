<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\University;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to handle listing all events for a specific university.
 *
 * Retrieves a paginated list of events ordered by the start date,
 * including the aggregate count of registrations.
 */
class ListUniversityEvents
{
    /**
     * Execute the event listing workflow.
     *
     * @param  University  $university  The university entity.
     * @param  int|null    $perPage     Optional pagination limit.
     * @return LengthAwarePaginator<int, Event>
     */
    public function handle(University $university, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('app.pagination.per_page', 20);

        return $university->events()
            ->withCount('registrations')
            ->latest('start_date')
            ->paginate((int) $perPage);
    }
}
