<?php

namespace App\V1\Actions\MentorshipRequest;

use App\Enums\MentorshipRequestStatus;
use App\Events\MentorshipRequestStatusUpdated;
use App\Jobs\SendMentorshipStatusNotification;
use App\Jobs\SendMentorshipWaitlistNotification;
use App\Jobs\SendMentorLimitReachedNotification;
use App\Models\MentorshipRequest;
use DB;
use DomainException;

class UpdateMentorshipRequestStatusAction
{
    /**
     * Execute the action to update the status of a mentorship request.
     *
     * @param MentorshipRequest $mentorshipRequest
     * @param MentorshipRequestStatus $newStatus
     * @return MentorshipRequest
     * @throws DomainException
     */
    public function handle(MentorshipRequest $mentorshipRequest, MentorshipRequestStatus $newStatus): MentorshipRequest
{
    return DB::transaction(function () use ($mentorshipRequest, $newStatus) {
   $previousStatus = $mentorshipRequest->status;

        throw_if(
            in_array($mentorshipRequest->status, [
                MentorshipRequestStatus::COMPLETED,
                MentorshipRequestStatus::REJECTED
            ]),
            DomainException::class,
            "Cannot modify a request that is already {$mentorshipRequest->status->value}."
        );

        throw_if(
            in_array($newStatus, [
                MentorshipRequestStatus::ACCEPTED,
                MentorshipRequestStatus::REJECTED
            ]) && $mentorshipRequest->status !== MentorshipRequestStatus::PENDING,
            DomainException::class,
            'Only pending requests can be accepted or rejected.'
        );

        throw_unless(
            $newStatus !== MentorshipRequestStatus::COMPLETED ||
            $mentorshipRequest->status === MentorshipRequestStatus::ACCEPTED,
            DomainException::class,
            'You can only complete an accepted mentorship session.'
        );

        $mentor = $mentorshipRequest->mentor;
        $programId = $mentorshipRequest->program_id;

        throw_if(
            $newStatus === MentorshipRequestStatus::ACCEPTED &&
            $mentor->hasReachedLimit($programId),
            DomainException::class,
            "Mentor has reached maximum capacity."
        );

        $mentorshipRequest->update([
            'status' => $newStatus,
        ]);

event(new MentorshipRequestStatusUpdated($mentorshipRequest, $previousStatus));

SendMentorshipStatusNotification::dispatch($mentorshipRequest)->afterCommit();
        if ($newStatus === MentorshipRequestStatus::ACCEPTED) {
            if ($mentor->hasReachedLimit($programId)) {

                SendMentorLimitReachedNotification::dispatch($mentor, $mentorshipRequest->program)->afterCommit();

                $pendingRequests = $mentor->receivedMentorshipRequests()
                    ->forProgram($programId)
                    ->pending()
                    ->where('id', '!=', $mentorshipRequest->id)
                    ->get();

                foreach ($pendingRequests as $pendingReq) {
                    SendMentorshipWaitlistNotification::dispatch($pendingReq)->afterCommit();
                }
            }
        }

        return $mentorshipRequest;
    });
}
}
