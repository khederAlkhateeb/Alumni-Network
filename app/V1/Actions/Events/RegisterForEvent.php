<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action to handle a user registering for an event.
 *
 * Enforces business rules:
 * - Uses pessimistic locking to prevent race conditions.
 * - Prevents registration if the event is at full capacity.
 * - Prevents duplicate registrations by the same user.
 */
class RegisterForEvent
{
    /**
     * Execute the registration workflow.
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
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->isFull()) {
                throw ValidationException::withMessages([
                    'event' => ['This event has reached its maximum capacity.'],
                ]);
            }

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
