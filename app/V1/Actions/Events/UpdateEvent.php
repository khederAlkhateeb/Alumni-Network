<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Enums\EventStatus;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle updating an existing university event's details.
 *
 * Enforces business rules:
 * - Prevents updates on completed or cancelled events.
 * - Prevents reducing capacity below the current registration count.
 */
class UpdateEvent
{
    /**
     * Execute the event update workflow.
     *
     * @param  Event  $event
     * @param  array{
     *     title?: string,
     *     description?: string|null,
     *     start_date?: string,
     *     end_date?: string,
     *     capacity?: int|null,
     *     type?: string,
     *     status?: string
     * }  $data
     * @return Event
     *
     * @throws ValidationException
     */
    public function handle(Event $event, array $data): Event
    {
        if (in_array($event->status, [EventStatus::Completed, EventStatus::Cancelled])) {
            throw ValidationException::withMessages([
                'event' => ['Cannot update an event that has already been completed or cancelled.'],
            ]);
        }

        if (array_key_exists('capacity', $data) && $data['capacity'] !== null) {
            $currentRegistrations = $event->registrations()->count();

            if ($data['capacity'] < $currentRegistrations) {
                throw ValidationException::withMessages([
                    'capacity' => ["Cannot reduce capacity below the current number of registered users ({$currentRegistrations})."],
                ]);
            }
        }

        $event->update($data);

        return $event->fresh();
    }
}
