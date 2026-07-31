<?php

namespace Database\Seeders;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\University;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void


    {

// data for testing
// for delete!!
$university = University::firstOrCreate(
            ['id' => 1],
            [
                'name'    => 'Damascus University',
                'country' => 'Syria',
            ]
        );


        $faculty = Faculty::firstOrCreate(
            ['id' => 1],
            [
                'university_id' => $university->id,
                'name'          => 'Faculty of Information Technology',
            ]
        );


        $major = Major::firstOrCreate(
            ['id' => 1],
            [
                'faculty_id' => $faculty->id,
                'name'       => 'Software Engineering',
            ]
        );

        $this->call([
            RoleAndPermissionSeeder::class,
            FacultySeeder::class,
             AlumniProfileSeeder::class,
            AlumniSkillSeeder::class,
            AlumniWorkExperienceSeeder::class,
        ]);

        // User
        $superAdmin = User::factory()->create([
            'email' => 'super@admin.com'
        ]);

        $role = Role::findByName('super_admin', 'api');
        $superAdmin->assignRole($role);

        // Universities
        University::factory(50)->create(['created_by' =>  1]);
    }
}
