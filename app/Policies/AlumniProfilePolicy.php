<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AlumniProfile;
use App\Models\User;

class AlumniProfilePolicy
{
    /**
     * super_admin true for all

     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->role === 'super_admin' ? true : null;
    }

    /**
     * GET /alumni —
     *
      */
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    /**
     * GET /alumni/{id}—
     */
    public function view(User $user, AlumniProfile $profile): bool
    {
return true;
// يجب تعديلاها لتلائم الشروط لا يرى  اي شي من غير جامعته
    }

    /**
     * PUT /alumni/me
     */
    public function update(User $user, AlumniProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }

    /**
     * POST/PUT/DELETE على work-experiences و skills
     * نفس شرط update: بس صاحب البروفايل (بدون استثناءات، حتى uni_admin ما يعدل)
     */

    //سيتم وضعها في الموديل المخصص

    /**
     * POST /alumni/me/toggle-mentor

     */
    public function toggleMentor(User $user, AlumniProfile $profile): bool
    {
        return $profile->user_id === $user->id
            && $profile->status === 'active';
    }

/**
 * POST /alumni/me/complete-profile
 */
public function completeProfile(User $user, AlumniProfile $profile): bool
{
    return $profile->user_id === $user->id;
}

}
