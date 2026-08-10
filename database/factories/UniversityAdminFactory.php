<?php

namespace Database\Factories;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

class UniversityAdminFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->afterCreating(function (User $user) {
                $role = Role::firstOrCreate(['name' => 'uni_admin', 'guard_name' => 'api']);
                $user->assignRole($role);
            }),
            'university_id' => University::factory(),
        ];
    }
}