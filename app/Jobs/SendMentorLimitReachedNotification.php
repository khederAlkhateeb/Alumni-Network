<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Notifications\MentorLimitReachedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMentorLimitReachedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


  /**
     * execute the job
     * @return void
     */
    public function __construct(public $mentor, public $program) {}

    public function handle(): void
    {
        if (!$this->mentor || !$this->program) {
            return;
        }

        Notification::create([
            'user_id'      => $this->mentor->id,
            'type'         => 'limit_reached',
            'related_id'   => $this->mentor->id,
            'related_type' => get_class($this->mentor),
            'message'      => "You have reached your limit of {$this->program->mentor_per_mentees_max} mentorship requests. New requests are temporarily paused.",
            'read_at'      => null,
        ]);
    }
}
