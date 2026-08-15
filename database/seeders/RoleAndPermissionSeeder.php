<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Forget cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions and roles
        $guardName = 'api';

        // Helper function to create permissions
        $createPermission = fn ($name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => $guardName,
        ]);

        // ==================== PERMISSIONS ====================

        // Universities & Academic Structure
        $createPermission('create-university');
        $createPermission('edit-university');
        $createPermission('delete-university');
        $createPermission('view-university-stats');
        $createPermission('view-faculties');
        $createPermission('manage-faculties');
        $createPermission('view-majors');
        $createPermission('manage-majors');

        // Registrations & Approvals
        $createPermission('view-pending-registrations');
        $createPermission('approve-alumni-registration');
        $createPermission('reject-alumni-registration');
        $createPermission('approve-student-registration');
        $createPermission('reject-student-registration');

        // Profiles & Experiences
        $createPermission('view-alumni-profiles');
        $createPermission('view-student-profiles');
        $createPermission('edit-own-profile');
        $createPermission('manage-work-experiences');
        $createPermission(' edit-own-profile');
        $createPermission('manage-skills');
        $createPermission('toggle-mentor-status');

        // Connections
        $createPermission('view-connections');
        $createPermission('send-connection-request');
        $createPermission('accept-connection-request');
        $createPermission('reject-connection-request');
        $createPermission('remove-connection');
        $createPermission('block-user');

        // Feed & Posts
        $createPermission('view-feed');
        $createPermission('create-post');
        $createPermission('edit-own-post');
        $createPermission('delete-own-post');
        $createPermission('delete-any-post');
        $createPermission('react-to-post');
        $createPermission('comment-on-post');
        $createPermission('delete-own-comment');
        $createPermission('delete-any-comment');

        // Job Listings
        $createPermission('view-jobs');
        $createPermission('create-job');
        $createPermission('edit-own-job');
        $createPermission('delete-own-job');
        $createPermission('delete-any-job');
        $createPermission('close-job');
        $createPermission('apply-for-job');
        $createPermission('view-job-applications');
        $createPermission('update-application-status');

        // Events
        $createPermission('view-events');
        $createPermission('create-event');
        $createPermission('edit-event');
        $createPermission('delete-event');
        $createPermission('register-for-event');
        $createPermission('mark-attendance');
        $createPermission('view-event-registrations');

        // Mentorship
        $createPermission('view-mentorship-programs');
        $createPermission('create-mentorship-program');
        $createPermission('edit-mentorship-program');
        $createPermission('activate-mentorship-program');
        $createPermission('close-mentorship-program');
        $createPermission('view-available-mentors');
        $createPermission('send-mentorship-request');
        $createPermission('accept-mentorship-request');
        $createPermission('reject-mentorship-request');
        $createPermission('complete-mentorship');

        // Direct Messages & Notifications
        $createPermission('view-conversations');
        $createPermission('send-message');
        $createPermission('mark-message-as-read');
        $createPermission('view-notifications');
        $createPermission('mark-notification-as-read');

        // Reports
        $createPermission('view-university-reports');

        // ==================== ROLES ====================

        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guardName]);
        $superAdmin->syncPermissions([
            'create-university', 'edit-university', 'delete-university', 'view-university-stats',
            'view-faculties', 'manage-faculties', 'view-majors', 'manage-majors',
            'view-alumni-profiles', 'view-student-profiles',
            'view-university-reports', 'view-notifications', 'mark-notification-as-read'
        ]);

        // University Admin
        $uniAdmin = Role::firstOrCreate(['name' => 'uni_admin', 'guard_name' => $guardName]);
        $uniAdmin->syncPermissions([
            'edit-university', 'view-university-stats',
            'view-faculties', 'manage-faculties', 'view-majors', 'manage-majors',
            'view-pending-registrations', 'approve-alumni-registration', 'reject-alumni-registration',
            'approve-student-registration', 'reject-student-registration',
            'view-alumni-profiles', 'view-student-profiles',
            'view-feed', 'react-to-post', 'comment-on-post', 'delete-any-comment', 'delete-any-post',
            'create-job', 'edit-own-job', 'delete-own-job', 'delete-any-job', 'close-job', 'view-jobs', 'view-job-applications', 'update-application-status',
            'create-event', 'edit-event', 'delete-event', 'view-events', 'view-event-registrations', 'mark-attendance',
            'create-mentorship-program', 'edit-mentorship-program', 'activate-mentorship-program', 'close-mentorship-program',
            'view-mentorship-programs', 'view-available-mentors',
            'view-conversations', 'send-message', 'mark-message-as-read',
            'view-notifications', 'mark-notification-as-read',
            'view-university-reports',
            'view-connections',
            'send-connection-request',
            'accept-connection-request',
            'reject-connection-request',
            'remove-connection'
        ]);

        // Alumni
        $alumni = Role::firstOrCreate(['name' => 'alumni', 'guard_name' => $guardName]);
        $alumni->syncPermissions([
            'view-faculties', 'view-majors',
            'view-alumni-profiles', 'view-student-profiles', 'edit-own-profile',
            'manage-work-experiences', 'manage-skills', 'toggle-mentor-status',
            'view-connections', 'send-connection-request', 'accept-connection-request', 'reject-connection-request', 'remove-connection', 'block-user',
            'view-feed', 'create-post', 'edit-own-post', 'delete-own-post', 'react-to-post', 'comment-on-post', 'delete-own-comment',
            'view-jobs', 'create-job', 'edit-own-job', 'delete-own-job', 'close-job', 'apply-for-job', 'view-job-applications', 'update-application-status',
            'view-events', 'register-for-event',
            'view-mentorship-programs', 'view-available-mentors', 'send-mentorship-request', 'accept-mentorship-request', 'reject-mentorship-request', 'complete-mentorship',
            'view-conversations', 'send-message', 'mark-message-as-read',
            'view-notifications', 'mark-notification-as-read'
        ]);

        // Current Student
        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => $guardName]);
        $student->syncPermissions([
            'view-faculties', 'view-majors',
            'view-alumni-profiles', 'view-student-profiles', 'edit-own-profile',
            'view-connections', 'send-connection-request', 'accept-connection-request', 'reject-connection-request', 'remove-connection', 'block-user',
            'view-feed', 'react-to-post', 'comment-on-post', 'delete-own-comment',
            'view-jobs', 'apply-for-job', 'view-job-applications',
            'view-events', 'register-for-event',
            'view-mentorship-programs', 'view-available-mentors', 'send-mentorship-request',
            'view-conversations', 'send-message', 'mark-message-as-read',
            'view-notifications', 'mark-notification-as-read'
        ]);
    }
}
