<?php

namespace App\V1\Actions\MentorshipRequest;

use App\Jobs\SendMentorLimitReachedNotification;
use App\Models\MentorshipRequest;
use App\Models\User;

/**
 * Class CreateMentorshipRequestAction
 *
 * Handles the business logic for creating a new mentorship request
 * on behalf of a mentee and checking capacity limits.
 *
 * @package App\V1\Actions\MentorshipRequest
 */
class CreateMentorshipRequestAction
{
    /**
     * Execute the action to create a mentorship request.
     *
     * @param  User  $mentee
     * @param  array<string, mixed>  $data
     * @return MentorshipRequest
     */
    public function handle(User $mentee, array $data): MentorshipRequest
    {
        $request = MentorshipRequest::create([
            'mentee_id'     => $mentee->id,
            'mentor_id'     => $data['mentor_id'],
            'program_id'    => $data['program_id'],
            'intro_message' => $data['intro_message'] ?? null,
            'status'        => \App\Enums\MentorshipRequestStatus::PENDING,
        ]);

        $mentor = $request->mentor;
        $program = $request->program;


        if ($mentor && $program && $mentor->hasReachedLimit($program->id)) {
            SendMentorLimitReachedNotification::dispatch($mentor, $program);
        }

        return $request;
    }
}
