<?php

namespace App\Services;

use App\Contracts\UniversityContext;
use App\Models\AlumniProfile;
use App\Models\StudentProfile;
use App\Models\UniversityAdmin;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;

/**
 * Implementation of UniversityContext for Laravel.
 * 
 * This service class provides the current user's university context by resolving
 * the university ID based on the authenticated user's role and profile associations.
 * It implements caching to avoid repeated database queries and optimizes query performance
 * by using direct JOINs instead of lazy-loaded relationships.
 */
class LaravelUniversityContext implements UniversityContext
{
    private ?int $universityId = null;
    private bool $resolved = false;

    /**
     * Create a new LaravelUniversityContext instance.
     * 
     * @param Guard $auth The authentication guard instance.
     */
    public function __construct(private readonly Guard $auth) {}

    /**
     * Get the resolved university ID for the current user context.
     * 
     * This method determines the university ID by checking the following sources in order:
     * 1. UniversityAdmin record (for uni_admin users)
     * 2. AlumniProfile -> Major -> Faculty -> University (for alumni users)
     * 3. StudentProfile -> Major -> Faculty -> University (for student users)
     * 
     * Results are cached for 1 hour (3600 seconds) to prevent N+1 queries across requests.
     * Uses direct JOIN queries for performance optimization (1 query per source instead of 3+).
     * 
     * @return int|null The university ID if found, null for guests or users without a university association.
     */
    public function getUniversityId(): ?int
    {
        if ($this->isGuest()) {
            return null;
        }

        if ($this->resolved) {
            return $this->universityId;
        }

        $user = $this->auth->user();

        // Use Cache to prevent N+1 queries across different requests
        $this->universityId = Cache::remember("user_{$user->id}_university_id", 3600, function () use ($user) {
            // Check universityAdmin first (direct query)
            // uni_admin users have a direct university_id in the university_admins table
            $universityId = UniversityAdmin::query()
                ->where('user_id', $user->id)
                ->value('university_id');

            if ($universityId !== null) {
                return $universityId;
            }

            // Check alumniProfile through JOIN queries
            // Uses direct JOINs instead of lazy-loaded relationships for performance
            // Reduces from 3 queries (profile -> major -> faculty) to 1 query
            $universityId = AlumniProfile::query()
                ->where('user_id', $user->id)
                ->join('majors', 'majors.id', '=', 'alumni_profiles.major_id')
                ->join('faculties', 'faculties.id', '=', 'majors.faculty_id')
                ->value('faculties.university_id');

            if ($universityId !== null) {
                return $universityId;
            }

            // Check studentProfile through JOIN queries
            // Same optimization as alumniProfile
            $universityId = StudentProfile::query()
                ->where('user_id', $user->id)
                ->join('majors', 'majors.id', '=', 'student_profiles.major_id')
                ->join('faculties', 'faculties.id', '=', 'majors.faculty_id')
                ->value('faculties.university_id');

            return $universityId;
        });

        $this->resolved = true;

        return $this->universityId;
    }

    /**
     * Check if the current user is a super admin.
     * 
     * Super admins have access to all universities and bypass tenant scoping.
     * 
     * @return bool True if the user has the 'super_admin' role, false otherwise.
     */
    public function isSuperAdmin(): bool
    {
        $user = $this->auth->user();
        return $user && $user->hasRole('super_admin');
    }

    /**
     * Check if the current user is a guest (unauthenticated).
     * 
     * @return bool True if the user is not authenticated, false otherwise.
     */
    public function isGuest(): bool
    {
        return $this->auth->guest();
    }
}
