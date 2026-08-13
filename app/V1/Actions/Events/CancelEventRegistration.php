<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle cancelling a user's registration for an event.
 *
 * Enforces business rules:
 * - Cancellation is prohibited once the event has already started.
 * - The user must have an existing registration for the event.
 */
class CancelEventRegistration
{
    /**
     * Execute the registration cancellation workflow.
     *
     * @param  Event  $event
     * @param  User   $user
     * @return void
     *
     * @throws ValidationException
     */
    public function handle(Event $event, User $user): void
    {
        DB::transaction(function () use ($event, $user) {
            if ($event->hasStarted()) {
                throw ValidationException::withMessages([
                    'event' => ['Cannot cancel registration after the event has started.'],
                ]);
            }

            $registration = $event->registrations()
                ->where('user_id', $user->id)
                ->first();

            if (! $registration) {
                throw ValidationException::withMessages([
                    'event' => ['You are not registered for this event.'],
                ]);
            }

            $registration->delete();
        });
    }
}
