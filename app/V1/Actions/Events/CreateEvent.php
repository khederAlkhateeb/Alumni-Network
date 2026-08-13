<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\University;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle the creation and persistence of a new university event.
 *
 * Enforces business rules such as ensuring new events are not scheduled in the past.
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
     *
     * @throws ValidationException
     */
    public function handle(University $university, array $data): Event
    {
        if (isset($data['start_date']) && Carbon::parse($data['start_date'])->isPast()) {
            throw ValidationException::withMessages([
                'start_date' => ['An event cannot be scheduled to start in the past.'],
            ]);
        }

        return $university->events()->create($data);
    }
}
