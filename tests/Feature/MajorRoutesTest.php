<?php

namespace Tests\Feature;

// Import Enums & Models required for setting up test fixtures and relationships
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

// Import Laravel Testing utilities
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Class MajorRoutesTest
 * 
 * Functional/Feature test suite covering endpoint accessibility, response formatting, 
 * and multi-tenant RBAC policies for Major management under the University > Faculty hierarchy.
 */
class MajorRoutesTest extends TestCase
{
    // Resets the database state after each test method runs to guarantee isolation
    use RefreshDatabase;

    /**
     * User instances representing different actors in the RBAC hierarchy.
     */
    private User $alumniUser;
    private User $studentUser;
    private User $uniAdminUser;
    private User $superAdminUser;

    /**
     * Contextual structural entities for nested routing.
     */
    private University $university;
    private Faculty $faculty;

    /**
     * Set up the initial test environment before each test method runs.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialize core system roles under the default 'api' guard
        Role::findOrCreate('alumni', 'api');
        Role::findOrCreate('student', 'api');
        Role::findOrCreate('uni_admin', 'api');
        Role::findOrCreate('super_admin', 'api');

        // Register required fine-grained permissions
        $this->createPermissions();

        // Create primary structural entities (University -> Faculty)
        $this->university = University::factory()->create();
        $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);

        // Instantiate an active Alumni user with an active profile
        $this->alumniUser = User::factory()->create(['is_active' => true]);
        $this->alumniUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $this->alumniUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        // Instantiate an active Student user with an active profile
        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
            'status' => ProfileStatus::ACTIVE,
        ]);

        // Instantiate a University Admin scoped strictly to the test university
        $this->uniAdminUser = User::factory()->create(['is_active' => true]);
        $this->uniAdminUser->assignRole('uni_admin');
        $this->uniAdminUser->universityAdmin()->create(['university_id' => $this->university->id]);

        // Instantiate a global Super Admin with full system privileges
        $this->superAdminUser = User::factory()->create(['is_active' => true]);
        $this->superAdminUser->assignRole('super_admin');
    }

    /**
     * Helper method to seed required domain permissions for the Major domain.
     */
    private function createPermissions(): void
    {
        $guardName = 'api';
        $permissions = [
            'view-majors', 
            'create-major', 
            'edit-own-major', 
            'delete-own-major', 
            'delete-any-major',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }
    }


    // =========================================================================
    // Major Routes Tests
    // =========================================================================

    /**
     * @test
     * Ensure guest/unauthenticated users are rejected with HTTP 401 Unauthorized.
     */
    public function test_unauthenticated_user_cannot_access_major_routes(): void
    {
        // Attempt to access list endpoint without credentials
        $this->getJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors")
             ->assertStatus(401);

        // Attempt to access create endpoint without credentials
        $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [])
             ->assertStatus(401);
    }

    /**
     * @test
     * Ensure authenticated users (e.g., Alumni) can fetch the list of majors with exact JSON structure.
     */
    public function test_authenticated_user_can_view_majors(): void
    {
        // Seed test data inside the current target faculty
        Major::factory()->count(3)->create(['faculty_id' => $this->faculty->id]);

        // Authenticate as an alumni user via Sanctum
        Sanctum::actingAs($this->alumniUser, ['*']);

        // Issue GET request to retrieve majors
        $response = $this->getJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors");

        // Assert success status code and unified API response architecture
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

    /**
     * @test
     * Ensure Super Admin can create majors anywhere across the entire system.
     */
    public function test_super_admin_can_create_major(): void
    {
        // Authenticate as global super admin
        Sanctum::actingAs($this->superAdminUser, ['*']);

        // Send POST request with major payload
        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Computer Science',
        ]);

        // Assert successful resource creation (201 Created)
        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Major created successfully');
    }

    /**
     * @test
     * Ensure University Admin can create majors under their assigned university.
     */
    public function test_uni_admin_can_create_major_for_own_university(): void
    {
        // Authenticate as university admin bound to $this->university
        Sanctum::actingAs($this->uniAdminUser, ['*']);

        // Send POST request within authorized scope
        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Electrical Engineering',
        ]);

        // Assert successful creation
        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Major created successfully');
    }

    /**
     * @test
     * Ensure multi-tenancy rules prevent University Admin from modifying another university.
     */
    public function test_uni_admin_cannot_create_major_for_other_university(): void
    {
        // Create an unmanaged external university and faculty branch
        $otherUniversity = University::factory()->create();
        $otherFaculty = Faculty::factory()->create(['university_id' => $otherUniversity->id]);

        // Authenticate as university admin belonging to a different university
        Sanctum::actingAs($this->uniAdminUser, ['*']);

        // Attempt creation on foreign route scope
        $response = $this->postJson("/api/v1/universities/{$otherUniversity->id}/faculties/{$otherFaculty->id}/majors", [
            'name' => 'Mathematics',
        ]);

        // Assert boundary violation returns 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * @test
     * Ensure standard user roles (Alumni/Student) are barred from creating majors.
     */
    public function test_regular_user_cannot_create_major(): void
    {
        // Authenticate as non-administrative user
        Sanctum::actingAs($this->alumniUser, ['*']);

        // Attempt restricted action
        $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
            'name' => 'Physics',
        ]);

        // Assert access denial
        $response->assertStatus(403);
    }
}