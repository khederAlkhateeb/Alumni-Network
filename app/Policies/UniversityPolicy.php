<?php

namespace App\Policies;

use App\Contracts\UniversityContext;
use App\Models\University;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UniversityPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(User $user, University $university): Response
    {
        return $user !== null
            ? Response::allow()
            : Response::deny('Unauthorized User', 401);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, University $university): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('uni_admin')) {
            $context = app(UniversityContext::class);
            $userUniversityId = $context->getUniversityId();
            return $userUniversityId !== null && $userUniversityId === $university->id;
        }

        return false;
    }

    public function delete(User $user, University $university): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * View reports for a specific university.
     * @param User $user
     * @param University $university
     * @return bool
     */
    public function viewReports(User $user, University $university): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('uni_admin')) {
            $context = app(UniversityContext::class);
            $userUniversityId = $context->getUniversityId();
            return $userUniversityId !== null && $userUniversityId === $university->id;
        }

        return false;
    }


    /**
     * Determine whether the user can view university KPI stats.
     */
    public function viewStats(User $user, University $university): Response
    {
        // Super Admin can view stats for ANY university
        if ($user->hasRole('super_admin')) {
            return Response::allow();
        }

        // Uni Admin can only view status for their assigned university
        if ($user->hasRole('uni_admin')) {
            $context = app(UniversityContext::class);
            $userUniversityId = $context->getUniversityId();

            if ($userUniversityId !== null && $userUniversityId === $university->id)
                return Response::allow();
        }

        // Reject all other access attempts (Students, Alumni, or Uni Admin targeting another ID)
        return Response::deny('University not found or access denied.') ;
    }
}
