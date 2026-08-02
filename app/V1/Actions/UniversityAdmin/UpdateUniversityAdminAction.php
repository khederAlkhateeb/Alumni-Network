<?php

namespace App\V1\Actions\UniversityAdmin;

use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UpdateUniversityAdminAction
{
    /**
     * Update university admin info directly.
     *
     * @param UniversityAdmin $universityAdmin
     * @param array $data
     * @return User
     */
    public function handle(UniversityAdmin $universityAdmin, array $data): User
    {
        $user = $universityAdmin->user;

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $user;
    }
}