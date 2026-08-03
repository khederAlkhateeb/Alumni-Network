<?php

namespace App\Enums;

/**
 * Enum ReactionType
 *
 * Represents the different types of reactions a user can leave on a reactable entity (e.g., Post, Comment).
 *
 * @package App\Enums
 */
enum ReactionType: string
{
    /** Represents a standard 'like' reaction. */
    case LIKE = 'like';

    /** Represents an 'insightful' or thoughtful reaction. */
    case INSIGHTFUL = 'insightful';

    /** Represents a 'celebrate' or congratulatory reaction. */
    case CELEBRATE = 'celebrate';
}
