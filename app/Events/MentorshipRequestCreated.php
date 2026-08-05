<?php

namespace App\Events;

use App\Models\MentorshipRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentorshipRequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public MentorshipRequest $mentorshipRequest)
    {
    }
}
