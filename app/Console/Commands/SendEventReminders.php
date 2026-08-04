<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Notifications\EventReminderNotification;
use Illuminate\Console\Command;

/*
|--------------------------------------------------------------------------
| Send Event Reminders Command
|--------------------------------------------------------------------------
|
| Finds all upcoming events starting within the next 24 hours and
| dispatches a queued reminder notification to every user registered
| for each event. Intended to run hourly via the scheduler so that
| events entering the 24-hour window are reliably caught.
|
*/

class SendEventReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a queued reminder notification to users registered for events starting within the next 24 hours.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $windowStart = now()->addHours(23);
        $windowEnd = now()->addHours(24);

        $events = Event::query()
            ->upcoming()
            ->startingBetween($windowStart, $windowEnd)
            ->with('registrations.user')
            ->get();

        $notifiedCount = 0;

        foreach ($events as $event) {
            foreach ($event->registrations as $registration) {
                $registration->user?->notify(new EventReminderNotification($event));
                $notifiedCount++;
            }
        }

        $this->info("Sent reminders for {$events->count()} event(s) to {$notifiedCount} user(s).");

        return self::SUCCESS;
    }
}
