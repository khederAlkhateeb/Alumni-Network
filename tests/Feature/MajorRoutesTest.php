<?php

// Import Enums & Models required for setting up test fixtures and relationships
use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;

// Import Laravel Testing utilities
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Resets the database state after each test method runs to guarantee isolation
uses(RefreshDatabase::class);

/**
 * Set up the initial test environment before each test method runs.
 */
beforeEach(function () {
    /** @var \Tests\TestCase $this */

    // Initialize core system roles under the default 'api' guard
    Role::findOrCreate('alumni', 'api');
    Role::findOrCreate('student', 'api');
    Role::findOrCreate('uni_admin', 'api');
    Role::findOrCreate('super_admin', 'api');

    // Register required fine-grained permissions
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
});

// =========================================================================
// Major Routes Tests
// =========================================================================

/**
 * Ensure guest/unauthenticated users are rejected with HTTP 401 Unauthorized.
 */
test('unauthenticated user cannot access major routes', function () {
    /** @var \Tests\TestCase $this */

    // Attempt to access list endpoint without credentials
    $this->getJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors")
         ->assertStatus(401);

    // Attempt to access create endpoint without credentials
    $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [])
         ->assertStatus(401);
});

/**
 * Ensure authenticated users (e.g., Alumni) can fetch the list of majors with exact JSON structure.
 */
test('authenticated user can view majors', function () {
    /** @var \Tests\TestCase $this */

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
});

/**
 * Ensure Super Admin can create majors anywhere across the entire system.
 */
test('super admin can create major', function () {
    /** @var \Tests\TestCase $this */

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
});

/**
 * Ensure University Admin can create majors under their assigned university.
 */
test('uni admin can create major for own university', function () {
    /** @var \Tests\TestCase $this */

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
});

/**
 * Ensure multi-tenancy rules prevent University Admin from modifying another university.
 */
test('uni admin cannot create major for other university', function () {
    /** @var \Tests\TestCase $this */

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
});

/**
 * Ensure standard user roles (Alumni/Student) are barred from creating majors.
 */
test('regular user cannot create major', function () {
    /** @var \Tests\TestCase $this */

    // Authenticate as non-administrative user
    Sanctum::actingAs($this->alumniUser, ['*']);

    // Attempt restricted action
    $response = $this->postJson("/api/v1/universities/{$this->university->id}/faculties/{$this->faculty->id}/majors", [
        'name' => 'Physics',
    ]);

    // Assert access denial
    $response->assertStatus(403);
});