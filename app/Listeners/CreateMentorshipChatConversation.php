<?php

namespace App\Listeners;

use App\Enums\MentorshipRequestStatus;
use App\Events\MentorshipRequestStatusUpdated;
use App\Models\Conversation;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreateMentorshipChatConversation implements ShouldQueue
{ public $afterCommit = true;

    public function handle(MentorshipRequestStatusUpdated $event): void
    {
        $request = $event->mentorshipRequest;

        if ($request->status !== MentorshipRequestStatus::ACCEPTED) {
            return;
        }

        $userOne = min($request->mentor_id, $request->mentee_id);
        $userTwo = max($request->mentor_id, $request->mentee_id);


        Conversation::firstOrCreate([
            'user_one_id' => $userOne,
            'user_two_id' => $userTwo,
        ]);
    }
}
