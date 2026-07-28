<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\User;
use App\Models\Notification;

class SendApproveNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public User $user)
    {
       
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Notification::create([
            'user_id'      => $this->user->id,
            'type'         => 'account_approval',
            'related_id'   => $this->user->id,
            'related_type' => User::class,
            'message'      => 'Your account registration has been approved successfully.',
            'read_at'      => null,
        ]);
    }
}
