<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Action to record attendance for a registered user.
 *
 * Enforces business rules:
 * - Attendance can only be recorded while the event is currently ongoing.
 */
class RecordEventAttendance
{
    /**
     * Record attendance for the specified user.
     *
     * @param  Event  $event
     * @param  array{
     *     user_id: int|string
     * }  $data
     * @return void
     *
     * @throws ValidationException
     */
    public function handle(Event $event, array $data): void
    {
        DB::transaction(function () use ($event, $data) {
            if (!$event->isOngoing()) {
                throw ValidationException::withMessages([
                    'event' => ['Attendance can only be recorded during the event timeframe.'],
                ]);
            }

            $registration = $event->registrations()
                ->where('user_id', $data['user_id'])
                ->firstOrFail();

            $registration->update([
                'attended_at' => now(),
            ]);
        });
    }
}
