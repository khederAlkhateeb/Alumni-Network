<?php
namespace App\Jobs;

use App\Models\Notification;
use App\Models\StudentProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job for sending a notification to the student when their graduation request is rejected.
 */
class SendGraduationRejectedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param StudentProfile $studentProfile
     * @param string $rejectionReason
     */
    public function __construct(
        public StudentProfile $studentProfile,
        public string $rejectionReason
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::create([
            'user_id'      => $this->studentProfile->user_id,
            'type'         => 'graduation_request_rejected',
            'related_id'   => $this->studentProfile->id,
            'related_type' => get_class($this->studentProfile),
            'message'      => "Your graduation request has been rejected. Reason: {$this->rejectionReason}",
            'read_at'      => null,
        ]);
    }
}
