<?php

namespace Tests\Feature;

use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Comment;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Major;
use App\Models\Post;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MajorRoutesTest extends TestCase
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
    }

    private function createPermissions(): void
    {
        $guardName = 'api';
        $permissions = [
            'view-majors', 'create-major', 'edit-own-major', 'delete-own-major', 'delete-any-major',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }
    }


    // ==================== Major Routes Tests ====================

    public function test_unauthenticated_user_cannot_access_major_routes(): void
    {
        $this->getJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors")->assertStatus(401);
        $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [])->assertStatus(401);
    }

    public function test_authenticated_user_can_view_majors(): void
    {
        Major::factory()->count(3)->create(['faculty_id' => $this->faculty->id]);

        Sanctum::actingAs($this->alumniUser, ['*']);

        $response = $this->getJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'faculty_id',
                    ]
                ],
                'message'
            ]);
    }

    public function test_super_admin_can_create_major(): void
    {
        Sanctum::actingAs($this->superAdminUser, ['*']);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Computer Science',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Major created successfully');
    }

    public function test_uni_admin_can_create_major_for_own_university(): void
    {
        Sanctum::actingAs($this->uniAdminUser, ['*']);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Electrical Engineering',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Major created successfully');
    }

    public function test_uni_admin_cannot_create_major_for_other_university(): void
    {
        $otherUniversity = University::factory()->create();
        $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);

        Sanctum::actingAs($this->uniAdminUser, ['*']);

        $response = $this->postJson("/api/v1/universities/{$otherUniversity->id}/faculties/{$otherFaculty->id}/majors", [
            'name' => 'Mathematics',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_major(): void
    {
        Sanctum::actingAs($this->alumniUser, ['*']);

        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Physics',
        ]);

        $response->assertStatus(403);
    }
}
