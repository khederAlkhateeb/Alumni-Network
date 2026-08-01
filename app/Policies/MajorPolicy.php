<?php

namespace App\Policies;

use App\Models\User;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Major;

class MajorPolicy
{
    /**
     * Determine whether the user can view the majors list.
     */
    public function viewAny(User $user): bool
    {
        return true; 
    }

    /**
     * Determine whether the user can create a major.
     */
    public function create(User $user, University $university, Faculty $faculty): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->hasRole('uni_admin')) {
            return $user->universityAdmin?->university_id === $university->id;
        }

        return false;
    }
}