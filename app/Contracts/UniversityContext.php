<?php

namespace App\Contracts;

/**
 * Interface UniversityContext
 * 
 * Provides a contract for retrieving tenancy and role metadata related to the
 * authenticated user's university. This decouples the Eloquent scoping and policies
 * from direct dependency on the request auth helpers.
 */
interface UniversityContext
{
    /**
     * Get the resolved university ID for the current context.
     * 
     * @return int|null The university ID, or null if the user has no assigned university or is a guest.
     */
    public function getUniversityId(): ?int;

    /**
     * Check if the current context belongs to a super admin.
     * 
     * @return bool True if the user is a super admin, false otherwise.
     */
    public function isSuperAdmin(): bool;

    /**
     * Check if the current context is a guest.
     * 
     * @return bool True if the user is unauthenticated, false otherwise.
     */
    public function isGuest(): bool;
}
