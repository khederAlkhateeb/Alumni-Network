<?php

namespace App\Models;

use App\Builders\MentorshipRequestBuilder;
use App\Enums\MentorshipRequestStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class MentorshipRequest
 *
 * Represents a mentorship application or request linking a mentor, mentee, and program.
 *
 * @package App\Models
 */
#[Fillable([
    'program_id',
    'mentor_id',
    'mentee_id',
    'intro_message',
    'status',
])]
#[Hidden(['created_at', 'updated_at'])]

class MentorshipRequest extends Model
{
    use HasFactory;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return MentorshipRequestBuilder
     */
    public function newEloquentBuilder($query): MentorshipRequestBuilder
    {
        return new MentorshipRequestBuilder($query);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MentorshipRequestStatus::class,
        ];
    }

    /**
     * Get the mentorship program associated with the request.
     *
     * @return BelongsTo
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(MentorshipProgram::class, 'program_id');
    }

    /**
     * Get the mentor user assigned to the request.
     *
     * @return BelongsTo
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the mentee user who submitted the request.
     *
     * @return BelongsTo
     */
    public function mentee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentee_id');
    }

    /**
     * Determine if the mentorship request is currently pending.
     *
     * @return Attribute
     */
    protected function isPending(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === MentorshipRequestStatus::PENDING
        );
    }

    /**
     * Determine if the mentorship request has been accepted.
     *
     * @return Attribute
     */
    protected function isAccepted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === MentorshipRequestStatus::ACCEPTED
        );
    }
}
