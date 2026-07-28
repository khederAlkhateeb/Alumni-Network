<?php

namespace App\Contracts;

interface UniversityContext
{
    /**
     * Get the resolved university ID for the current context.
     */
    public function getUniversityId(): ?int;

    /**
     * Check if the current context belongs to a super admin.
     */
    public function isSuperAdmin(): bool;

    /**
     * Check if the current context is a guest.
     */
    public function isGuest(): bool;
}
