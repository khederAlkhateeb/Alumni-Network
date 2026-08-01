<?php

namespace App\V1\Actions\Events;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| Record Event Attendance Action
|--------------------------------------------------------------------------
|
| Handles the business logic for marking a registered user as having
| attended an event. Attendance may only be recorded while the event
| is actually taking place (between its start and end dates).
|
*/

class RecordEventAttendance
{
    /**
     * Record attendance for the user referenced in $data.
     *
     * @param  Event  $event
     * @param  array{user_id: int|string}  $data
     * @return void
     *
     * @throws ValidationException
     */
    public function handle(Event $event, array $data): void
    {
        DB::transaction(function () use ($event, $data) {
            // Rule 6.4: attendance can only be recorded within the event's timeframe.
            if (!now()->between($event->start_date, $event->end_date)) {
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
