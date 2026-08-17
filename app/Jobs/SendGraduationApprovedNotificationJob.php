<?php
namespace App\Jobs;

use App\Models\Notification;
use App\Models\AlumniProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job for sending a notification to the student when their graduation request is approved.
 */
class SendGraduationApprovedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param AlumniProfile $alumniProfile
     */
    public function __construct(public AlumniProfile $alumniProfile) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::create([
            'user_id'      => $this->alumniProfile->user_id,
            'type'         => 'graduation_request_approved',
            'related_id'   => $this->alumniProfile->id,
            'related_type' => get_class($this->alumniProfile),
            'message'      => 'Your graduation request has been successfully approved! Please complete your alumni profile to fully activate it.',
            'read_at'      => null,
        ]);
    }
}
