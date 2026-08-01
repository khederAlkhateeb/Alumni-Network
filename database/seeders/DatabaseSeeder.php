<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([


            RoleAndPermissionSeeder::class,
            UniversitySeeder::class,
            FacultySeeder::class,
            MajorSeeder::class,
            UserSeeder::class,
            SkillSeeder::class,

            StudentProfileSeeder::class,
            AlumniProfileSeeder::class,
            WorkExperienceSeeder::class,
            SkillSeeder::class,

            ConnectionSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
            ReactionSeeder::class,

            JobSeeder::class,
            JobApplicationSeeder::class,

            EventSeeder::class,
            EventRegistrationSeeder::class,

            MentorshipProgramSeeder::class,
            MentorshipRequestSeeder::class,

            ConversationSeeder::class,
            MessageSeeder::class,

            NotificationSeeder::class,
            AttachmentSeeder::class,

            ReportSnapshotSeeder::class,
        ]);
<<<<<<< HEAD
=======

        // User
        $superAdmin = User::factory()->create([
            'email' => 'super@admin.com'
        ]);

        $role = Role::findByName('super_admin', 'api');
        $superAdmin->assignRole($role);

        // Universities
        University::factory(50)->create(['created_by' =>  1]);
>>>>>>> b4ad8f1fb721c93e789548f1a9ed7574c1a5bce4
    }
}
