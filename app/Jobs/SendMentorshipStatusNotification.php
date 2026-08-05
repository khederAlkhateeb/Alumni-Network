<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\MentorshipRequest;
use App\Notifications\MentorshipStatusChangedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMentorshipStatusNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
     * execute the job
     * @return void
     */
    public function __construct(public MentorshipRequest $mentorshipRequest) {}

    public function handle(): void
    {
        $mentee = $this->mentorshipRequest->mentee;

        if (!$mentee) {
            return;
        }

        Notification::create([
            'user_id'      => $mentee->id,
            'type'         => 'mentorship_status_changed',
            'related_id'   => $this->mentorshipRequest->id,
            'related_type' => get_class($this->mentorshipRequest),
            'message'      => "Your mentorship request is : " . $this->mentorshipRequest->status->value,
            'read_at'      => null,
        ]);
    }
}
