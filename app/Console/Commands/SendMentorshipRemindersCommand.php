<?php

namespace App\Console\Commands;

use App\Enums\MentorshipRequestStatus;
use App\Models\MentorshipRequest;
use App\Notifications\MentorshipReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMentorshipRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mentorship:send-reminders {hours=24} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications to mentors and mentees for upcoming sessions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Initializing mentorship session reminder pipeline...');

        $hours = (int) $this->argument('hours');

        $targetDate = Carbon::now()->addHours($hours)->toDateString();

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('!! DRY RUN MODE ENABLED !! No notifications will be dispatched to users.');
        }

        $this->comment("Target configuration: Searching for accepted sessions with programs starting on {$targetDate}");

        $query = MentorshipRequest::query()
            ->where('status', MentorshipRequestStatus::ACCEPTED)
            ->whereHas('program', function ($q) use ($targetDate) {
                $q->whereDate('start_date', $targetDate);
            })
            ->with(['mentor', 'mentee', 'program']);

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No eligible upcoming mentorship sessions found for reminders.');
            return self::SUCCESS;
        }

        $this->warn("Found {$totalCount} upcoming sessions pending notifications. Processing batches...");
        $processedCount = 0;

        $query->chunkById(100, function ($requests) use (&$processedCount, $totalCount, $isDryRun) {
            foreach ($requests as $request) {
                /** @var MentorshipRequest $request */

                if (!$isDryRun) {
                    if ($request->mentor) {
                        $request->mentor->notify(new MentorshipReminderNotification($request, 'mentor'));
                    }

                    if ($request->mentee) {
                        $request->mentee->notify(new MentorshipReminderNotification($request, 'mentee'));
                    }
                }

                $processedCount++;
            }

            $statusText = $isDryRun ? "Simulated" : "Dispatched";
            $this->line("Batch processed. Current progress: [{$processedCount}/{$totalCount}] sessions {$statusText}.");
        });

        $finalMessage = $isDryRun
            ? "Dry run sequence executed successfully. Total simulated reminder notifications: {$processedCount} sessions."
            : "Reminder sequence executed successfully. Total notifications dispatched for: {$processedCount} sessions.";

        $this->info($finalMessage);

        return self::SUCCESS;
    }
}
