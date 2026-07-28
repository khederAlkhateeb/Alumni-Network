<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Roles & Permissions
        $this->call([
            RoleAndPermissionSeeder::class,
        ]);

        // User
        User::factory()->create([
            'email' => 'super@admin.com'
        ])->assignRole('super_admin')->save();

        // Universities
        University::factory(50)->create(['created_by' =>  1]);
    }
}
