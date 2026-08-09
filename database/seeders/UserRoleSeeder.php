<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'api';

        $roles = [
            'super_admin',
            'uni_admin',
            'alumni',
            'student',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, $guardName);
        }

        $usersData = [
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@example.com',
                'role'     => 'super_admin',
                'is_active' => true,
            ],
            [
                'name'     => 'University Admin',
                'email'    => 'uniadmin@example.com',
                'role'     => 'uni_admin',
            ],
            [
                'name'     => 'Faculty Admin',
                'email'    => 'facultyadmin@example.com',
                'role'     => 'alumni',
            ],
            [
                'name'     => 'Normal User',
                'email'    => 'user@example.com',
                'role'     => 'student',
            ],
        ];

        foreach ($usersData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => $data['name'],
                    'password' => Hash::make('password'),
                    'is_active' => $data['is_active'] ?? false,
                ]
            );

            $user->assignRole($data['role']);
        }
    }
}