<?php

namespace App\Enums;

/**
 * Defines the status lifecycle of a mentorship request.
 *
 * @package App\Enums
 */
enum MentorshipRequestStatus: string
{
    /** The request has been submitted and is awaiting review. */
    case PENDING = 'pending';

    /** The request has been accepted by the mentor. */
    case ACCEPTED = 'accepted';

    /** The request has been declined. */
    case REJECTED = 'rejected';

    /** The mentorship session or request has been successfully completed. */
    case COMPLETED = 'complete';
}
