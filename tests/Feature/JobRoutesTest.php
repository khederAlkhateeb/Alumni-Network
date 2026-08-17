<?php

namespace Tests\Feature;

use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JobRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $alumniUser;
    private User $studentUser;
    private User $uniAdminUser;
    private User $superAdminUser;
    private University $university;
    private Faculty $faculty;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('alumni', 'api');
        Role::findOrCreate('student', 'api');
        Role::findOrCreate('uni_admin', 'api');
        Role::findOrCreate('super_admin', 'api');

        $this->createPermissions();

        $this->university = University::factory()->create();
        $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        $this->alumniUser = User::factory()->create(['is_active' => true]);
        $this->alumniUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $this->alumniUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        $this->uniAdminUser = User::factory()->create(['is_active' => true]);
        $this->uniAdminUser->assignRole('uni_admin');
        $this->uniAdminUser->universityAdmin()->create(['university_id' => $this->university->id]);

        $this->superAdminUser = User::factory()->create(['is_active' => true]);
        $this->superAdminUser->assignRole('super_admin');


        $universityId = $this->university->id;

    app()->bind(\App\Contracts\UniversityContext::class, function () use ($universityId) {
        return new class($universityId) implements \App\Contracts\UniversityContext {
            private $id;
            public function __construct($id) { $this->id = $id; }
            public function isGuest(): bool { return false; }
            public function isSuperAdmin(): bool { return false; }
            public function getUniversityId(): ?int { return $this->id; }
        };
    });
    }
    private function createPermissions(): void
    {
        $guardName = 'api';
        $permissions = [
            'view-jobs',
            'create-job',
            'edit-own-job',
            'delete-own-job',
            'delete-any-job',
            'close-job',
            'apply-for-job',
            'view-job-applications',
            'update-application-status',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }
    }


    public function test_unauthenticated_user_cannot_access_job_routes(): void
    {
        $this->getJson('/api/v1/jobs')->assertStatus(401);
        $this->postJson('/api/v1/jobs', [])->assertStatus(401);
        $this->getJson('/api/v1/jobs/1')->assertStatus(401);
        $this->putJson('/api/v1/jobs/1', [])->assertStatus(401);
        $this->deleteJson('/api/v1/jobs/1')->assertStatus(401);
        $this->patchJson('/api/v1/jobs/1/close')->assertStatus(401);
        $this->postJson('/api/v1/jobs/1/apply', [])->assertStatus(401);
        $this->getJson('/api/v1/jobs/my-applications')->assertStatus(401);
        $this->getJson('/api/v1/jobs/1/applications')->assertStatus(401);
        $this->patchJson('/api/v1/jobs/1/applications/1/status', [])->assertStatus(401);
    }

    public function test_authenticated_user_can_view_jobs(): void
    {
        JobListing::factory()->count(3)->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('view-jobs');

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'company',
                        'location',
                        'type',
                        'status',
                    ]
                ],
                'message'
            ]);
    }

    public function test_authenticated_user_can_view_single_job(): void
    {
        $job = JobListing::factory()->create(['university_id' => $this->university->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('view-jobs');

        $response = $this->getJson("/api/v1/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $job->id);
    }

    public function test_user_with_permission_can_create_job(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('create-job');

        $response = $this->postJson('/api/v1/jobs', [
            'university_id' => $this->university->id,
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
            'location' => 'Amman',
            'type' => 'full_time',
            'description' => 'A great job opportunity',
            'requirements' => 'Experience required',
            'salary_range' => '1000-2000 JOD',
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job listing created successfully.');
    }

    public function test_user_without_create_job_permission_cannot_create_job(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*']);

        $response = $this->postJson('/api/v1/jobs', [
            'university_id' => $this->university->id,
            'title' => 'Software Engineer',
            'company' => 'Tech Corp',
            'location' => 'Amman',
            'type' => 'full_time',
            'description' => 'A great job opportunity',
        ]);

        $response->assertStatus(403);
    }

    public function test_job_owner_can_update_job(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'posted_by_user_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('edit-own-job');

        $response = $this->putJson("/api/v1/jobs/{$job->id}", [
            'title' => 'Updated Job Title',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job listing updated successfully.');
    }

    public function test_job_owner_can_delete_job(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'posted_by_user_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('delete-own-job');

        $response = $this->deleteJson("/api/v1/jobs/{$job->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job listing deleted successfully.');

        $this->assertDatabaseMissing('job_listings', ['id' => $job->id]);
    }

    public function test_job_owner_can_close_job(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'posted_by_user_id' => $this->alumniUser->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('close-job');

        $response = $this->patchJson("/api/v1/jobs/{$job->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job listing closed successfully.');
    }

    public function test_user_can_apply_for_job(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('apply-for-job');

        $response = $this->postJson("/api/v1/jobs/{$job->id}/apply", [
            'cover_letter' => 'I am interested in this position.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job application submitted successfully.');
    }

    public function test_user_can_view_my_applications(): void
    {
        $job = JobListing::factory()->create(['university_id' => $this->university->id]);
        JobApplication::factory()->create([
            'job_listing_id' => $job->id,
            'applicant_id' => $this->alumniUser->id,
        ]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('apply-for-job');

        $response = $this->getJson('/api/v1/jobs/my-applications');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data',
                'message'
            ]);
    }

    public function test_job_owner_can_view_applications(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'posted_by_user_id' => $this->alumniUser->id,
        ]);
        JobApplication::factory()->count(3)->create(['job_listing_id' => $job->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('view-job-applications');

        $response = $this->getJson("/api/v1/jobs/{$job->id}/applications");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data',
                'message'
            ]);
    }

    public function test_job_owner_can_update_application_status(): void
    {
        $job = JobListing::factory()->create([
            'university_id' => $this->university->id,
            'posted_by_user_id' => $this->alumniUser->id,
        ]);
        $application = JobApplication::factory()->create(['job_listing_id' => $job->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);
        $this->alumniUser->givePermissionTo('update-application-status');

        $response = $this->patchJson("/api/v1/jobs/{$job->id}/applications/{$application->id}/status", [
            'status' => 'reviewed',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Job application status updated successfully.');
    }


}
