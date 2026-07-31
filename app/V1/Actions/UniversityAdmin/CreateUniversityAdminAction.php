<?php

namespace App\V1\Actions\UniversityAdmin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\UniversityAdmin;
use App\Models\University;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class CreateUniversityAdminAction
{
    /**
     * Create university admin user and link it to the specified university.
     *
     * @param array $data
     * @return User
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // create the user with the provided data
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);;

            // assign the 'university_admin' role to the user
            $role = Role::findByName('uni_admin', 'api');
            $user->assignRole($role);

            // create a new UniversityAdmin record linking the user to the university
            UniversityAdmin::create([
                'user_id' => $user->id,
                'university_id' => $data['university_id'],
            ]);

            return $user;
        });
    }
}