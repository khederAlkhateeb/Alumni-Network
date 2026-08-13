<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Action to list all registrations for a specific event.
 *
 * Pure read operation. Retrieves a paginated list of users registered
 * for the event, eager loading the associated user data.
 */
class ListEventRegistrations
{
    /**
     * Execute the event registrations listing workflow.
     *
     * @param  Event  $event
     * @param  int|null  $perPage
     * @return LengthAwarePaginator<int, EventRegistration>
     */
    public function handle(Event $event, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('app.pagination.per_page', 20);

        return $event->registrations()
            ->with('user')
            ->paginate((int) $perPage);
    }
}
