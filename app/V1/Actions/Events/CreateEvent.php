<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\University;

/**
 * Action to handle the creation and persistence of a new university event.
 *
 * Date validation (e.g. preventing past start dates) is handled entirely
 * by StoreEventRequest to avoid duplicated, potentially inconsistent
 * checks between the request layer and the action layer.
 */
class CreateEvent
{
    /**
     * Execute the event creation workflow.
     *
     * @param  University  $university
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     start_date: string,
     *     end_date: string,
     *     capacity?: int|null,
     *     type: string,
     *     status?: string
     * }  $data
     * @return Event
     */
    public function handle(University $university, array $data): Event
    {
        return $university->events()->create($data);
    }
}
