<?php

namespace Database\Seeders;

use App\Models\User;
use DB;
use Hash;
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


        $this->call([
            RoleAndPermissionSeeder::class,
            FacultySeeder::class,
             AlumniProfileSeeder::class,
            AlumniSkillSeeder::class,
            AlumniWorkExperienceSeeder::class,
        ]);
    }
}
