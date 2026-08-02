<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UniversityAdmin;
use App\Models\University;

class UniversityAdminPolicy
{
    /**
     * Create a new policy instance.
     */
        public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        return null;
    }

    /**
     * only super admin can create university admins, so this method always returns false for other users.
     *
     * @param User $user
     * @return boolean
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * only super admin can view university admins, so this method always returns false for other users.
     *
     * @param User $user
     * @param UniversityAdmin $universityAdmin
     * @return boolean
     */
    public function view(User $user, UniversityAdmin $universityAdmin): bool
    {
        return false;
    }

    /**
     * only super admin can assign university admins, so this method always returns false for other users.
     *
     * @param User $user
     * @param University $university
     * @return boolean
     */
    public function assignAdmin(User $user, University $university): bool
    {
        $hasAdmin = UniversityAdmin::where('university_id', $university->id)->exists();
        
        return ! $hasAdmin;
    }

    /**
     * only super admin can update university admins, so this method always returns false for other users.
     *
     * @param User $user
     * @param UniversityAdmin $universityAdmin
     * @return boolean
     */
    public function update(User $user, UniversityAdmin $universityAdmin): bool
    {
        return false;
    }

    /**
     * only super admin can view university admins list , so this method always returns false for other users.
     *
     * @param User $user
     * @param UniversityAdmin $universityAdmin
     * @return boolean
     */
    public function viewAny(User $user): bool
    {
        return false;
    }
    
}
