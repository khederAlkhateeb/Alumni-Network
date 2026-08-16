<?php
namespace App\Jobs;

use App\Models\Notification;
use App\Models\StudentProfile;
use App\Models\UniversityAdmin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job for sending notifications to university administrators when a student submits a new graduation request.
 */
class SendGraduationRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param StudentProfile $studentProfile
     */
    public function __construct(public StudentProfile $studentProfile) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $faculty = $this->studentProfile->major->faculty;

        if (!$faculty) {
            return;
        }

        // Fetch user IDs of the admins belonging to the relevant university
        $adminUserIds = UniversityAdmin::where('university_id', $faculty->university_id)
            ->pluck('user_id');

        $studentName = $this->studentProfile->user->name ?? 'Student';

        // Create a notification record for each admin in the notifications table
        foreach ($adminUserIds as $adminUserId) {
            Notification::create([
                'user_id'      => $adminUserId,
                'type'         => 'graduation_request_submitted',
                'related_id'   => $this->studentProfile->id,
                'related_type' => get_class($this->studentProfile),
                'message'      => "Student {$studentName} has submitted a graduation request and it is pending review.",
                'read_at'      => null,
            ]);
        }
    }
}
