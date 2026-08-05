<?php

namespace App\Builders;

use App\Enums\MentorshipRequestStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MentorshipRequestBuilder
 *
 * Custom Eloquent query builder for handling mentorship request scopes and filters.
 *
 * @package App\Builders
 * @extends Builder<\App\Models\MentorshipRequest>
 */
class MentorshipRequestBuilder extends Builder
{
    /**
     * Filter queries to only include pending mentorship requests.
     *
     * @return $this
     */
    public function pending(): self
    {
        return $this->where('status', MentorshipRequestStatus::PENDING);
    }

    /**
     * Filter queries to only include accepted mentorship requests.
     *
     * @return $this
     */
    public function accepted(): self
    {
        return $this->where('status', MentorshipRequestStatus::ACCEPTED);
    }

    /**
     * Filter queries to only include rejected mentorship requests.
     *
     * @return $this
     */
    public function rejected(): self
    {
        return $this->where('status', MentorshipRequestStatus::REJECTED);
    }

    /**
     * Filter queries to only include completed mentorship requests.
     *
     * @return $this
     */
    public function completed(): self
    {
        return $this->where('status', MentorshipRequestStatus::COMPLETED);
    }

    /**
     * Filter queries for a specific user (either as a mentor or a mentee).
     *
     * @param int $userId
     * @return $this
     */
    public function forUser(int $userId): self
    {
        return $this->where(function (Builder $query) use ($userId) {
            $query->where('mentor_id', $userId)
                  ->orWhere('mentee_id', $userId);
        });
    }

    /**
     * Filter queries for a specific mentor.
     *
     * @param int $mentorId
     * @return $this
     */
    public function forMentor(int $mentorId): self
    {
        return $this->where('mentor_id', $mentorId);
    }

    /**
     * Filter queries for a specific mentee.
     *
     * @param int $menteeId
     * @return $this
     */
    public function forMentee(int $menteeId): self
    {
        return $this->where('mentee_id', $menteeId);
    }

    /**
     * Filter queries for a specific mentorship program.
     *
     * @param int $programId
     * @return $this
     */
    public function forProgram(int $programId): self
    {
        return $this->where('program_id', $programId);
    }
}
