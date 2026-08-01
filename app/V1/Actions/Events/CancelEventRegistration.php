<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Cancel Event Registration Action
|--------------------------------------------------------------------------
|
| Handles the business logic for cancelling a user's registration to
| an event. Cancellation is only allowed before the event has started.
|
*/

class CancelEventRegistration
{
    /**
     * Cancel the given user's registration for the given event.
     *
     * @param  Event  $event
     * @param  User  $user
     * @return void
     *
     * @throws ValidationException
     */
    public function handle(Event $event, User $user): void
    {
        DB::transaction(function () use ($event, $user) {
            // Rule 6.4: registration cannot be cancelled once the event has started.
            if (now()->isAfter($event->start_date)) {
                throw ValidationException::withMessages([
                    'event' => ['Cannot cancel registration after the event has started.'],
                ]);
            }

            $registration = $event->registrations()
                ->where('user_id', $user->id)
                ->firstOrFail();

            $registration->delete();
        });
    }
}
