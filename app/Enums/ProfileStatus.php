<?php

namespace App\Enums;

/**
 * Enum ProfileStatus
 *
 * Represents the possible status values for student or alumni profiles.
 *
 * This enum is used to ensure type‑safe and consistent status handling
 * across the application, especially in:
 * - Eloquent models
 * - Query builders (e.g., StudentProfileBuilder)
 * - Policies
 * - Actions and business logic
 *
 * Available statuses:
 * - PENDING:     The profile is awaiting approval.
 * - ACTIVE:      The profile is approved and fully active.
 * - SUSPENDED:   The profile is temporarily disabled or blocked.
 */
enum ProfileStatus: string
{
    /** Profile is awaiting approval */
    case PENDING = 'pending';

    /** Profile is active and approved */
    case ACTIVE = 'active';

    /** Profile is suspended or blocked */
    case SUSPENDED = 'suspended';
}
