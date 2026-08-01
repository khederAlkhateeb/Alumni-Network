<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Register For Event Action
|--------------------------------------------------------------------------
|
| Handles the business logic for registering a user to an event:
| - Prevents registration once the event has reached full capacity.
| - Prevents duplicate registrations for the same user.
| Wrapped in a DB transaction with a row lock on the event to avoid
| race conditions when multiple users register at the same time.
|
*/

class RegisterForEvent
{
    /**
     * Register the given user for the given event.
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
            // Lock the event row to prevent concurrent registrations
            // from bypassing the capacity check.
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            // Rule 6.4: prevent registration once the event is at full capacity.
            // A null capacity means the event has no attendance limit.
            if (!is_null($lockedEvent->capacity) && $lockedEvent->registrations()->count() >= $lockedEvent->capacity) {
                throw ValidationException::withMessages([
                    'event' => ['This event has reached its maximum capacity.'],
                ]);
            }

            // Prevent the same user from registering twice for the same event.
            if ($lockedEvent->registrations()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'event' => ['You are already registered for this event.'],
                ]);
            }

            $lockedEvent->registrations()->create([
                'user_id'       => $user->id,
                'registered_at' => now(),
            ]);
        });
    }
}
