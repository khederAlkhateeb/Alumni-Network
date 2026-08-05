<?php

namespace App\Events;

use App\Models\MentorshipRequest;
use App\Enums\MentorshipRequestStatus;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentorshipRequestStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MentorshipRequest $mentorshipRequest,
        public MentorshipRequestStatus $previousStatus
    ) {}
}
