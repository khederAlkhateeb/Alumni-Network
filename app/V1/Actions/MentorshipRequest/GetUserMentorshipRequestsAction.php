<?php

namespace App\V1\Actions\MentorshipRequest;

use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class GetUserMentorshipRequestsAction
 *
 * Handles retrieving a paginated list of mentorship requests for a given user,
 * supporting both incoming (as a mentor) and outgoing (as a mentee) requests.
 *
 * @package App\V1\Actions\MentorshipRequest
 */
class GetUserMentorshipRequestsAction
{
    /**
     * Execute the action to fetch user mentorship requests with pagination and relations.
     *
     * @param  User  $user
     * @param  string  $type  The type of requests ('incoming' or 'outgoing')
     * @param  int  $perPage
     * @return LengthAwarePaginator
     */
    public function handle(User $user, string $type = 'incoming', int $perPage = 15): LengthAwarePaginator
    {
        $column = ($type === 'incoming') ? 'mentor_id' : 'mentee_id';

        return MentorshipRequest::query()
            ->where($column, $user->id)
            ->with(['mentor', 'mentee', 'program'])
            ->latest()
            ->paginate($perPage);
    }
}
