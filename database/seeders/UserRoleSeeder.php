<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\University;
use App\Models\UniversityAdmin;
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
                'is_active' => true,

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
        $university = University::first() ?? University::factory()->create();


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

            if ($data['role'] === 'uni_admin') {
                UniversityAdmin::updateOrCreate(
                    ['user_id' => $user->id],
                    ['university_id' => $university->id]
                );
            }
        }
    }
}
