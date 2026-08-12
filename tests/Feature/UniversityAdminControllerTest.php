<?php

// Import Models required for managing university administration entities and authentication
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;

// Import System Seeders
use Database\Seeders\RoleAndPermissionSeeder;

// Import Laravel Testing utilities
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

// Resets the database state after each test method runs to guarantee test isolation
uses(RefreshDatabase::class);

/**
 * Set up the initial test environment before each test method runs.
 */
beforeEach(function () {
    /** @var \Tests\TestCase $this */

    // Seed default system roles and permissions via dedicated seeder
    $this->seed(RoleAndPermissionSeeder::class);

    // Instantiate an active Super Admin with full management privileges
    $this->superAdmin = User::factory()->create(['is_active' => true]);
    $this->superAdmin->assignRole('super_admin');

    /**
     * Helper closure to authenticate the request as the Super Admin via Sanctum.
     */
    $this->actingAsSuperAdmin = function (): void {
        Sanctum::actingAs($this->superAdmin, ['*']);
    };
});

// =========================================================================
// Access Control & Role Enforcement (RBAC) Tests
// =========================================================================

/**
 * Ensure unauthenticated guests are denied access to University Admin management with HTTP 401.
 */
test('guest cannot access university admins', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson('/api/v1/university-admins')
         ->assertStatus(401);
});

/**
 * Ensure scoped University Admins (`uni_admin`) are forbidden from managing or creating other University Admins.
 */
test('uni admin is forbidden from managing university admins', function () {
    /** @var \Tests\TestCase $this */

    // Authenticate as a University Admin
    $uniAdmin = User::factory()->create(['is_active' => true]);
    $uniAdmin->assignRole('uni_admin');

    Sanctum::actingAs($uniAdmin, ['*']);

    // Attempt listing accounts -> expect 403 Forbidden
    $this->getJson('/api/v1/university-admins')
         ->assertStatus(403);

    // Attempt creating an account -> expect 403 Forbidden
    $university = University::factory()->create();
    $this->postJson('/api/v1/university-admins', [
        'name' => 'New Admin',
        'email' => 'new-admin@example.com',
        'password' => 'password123',
        'university_id' => $university->id,
    ])->assertStatus(403);
});

// =========================================================================
// CRUD & Business Rule Validation Tests
// =========================================================================

/**
 * Ensure Super Admin can retrieve a paginated listing of all registered University Admin entities.
 */
test('super admin can list university admins', function () {
    /** @var \Tests\TestCase $this */

    // Seed 3 University Admin records
    UniversityAdmin::factory()->count(3)->create();

    ($this->actingAsSuperAdmin)();

    // Issue GET request and verify paginated data count
    $this->getJson('/api/v1/university-admins')
        ->assertOk()
        ->assertJson(['status' => 'success'])
        ->assertJsonCount(3, 'data.data');
});

/**
 * Ensure Super Admin can create a new University Admin, properly assigning the `uni_admin` role
 * and persisting records in both `users` and `university_admins` tables.
 */
test('super admin can create a university admin', function () {
    /** @var \Tests\TestCase $this */

    $university = University::factory()->create();

    ($this->actingAsSuperAdmin)();

    // Issue POST request with creation payload
    $response = $this->postJson('/api/v1/university-admins', [
        'name' => 'Jane Admin',
        'email' => 'jane-admin@example.com',
        'password' => 'password123',
        'university_id' => $university->id,
    ]);

    // Assert HTTP 201 Created and verify response body structure
    $response->assertCreated()
        ->assertJson(['status' => 'success'])
        ->assertJsonPath('data.email', 'jane-admin@example.com');

    // Assert database persistence across related tables
    $this->assertDatabaseHas('users', ['email' => 'jane-admin@example.com']);
    $this->assertDatabaseHas('university_admins', ['university_id' => $university->id]);

    // Assert 'uni_admin' role assignment
    $createdUser = User::where('email', 'jane-admin@example.com')->first();
    $this->assertTrue($createdUser->hasRole('uni_admin'));
});

/**
 * Ensure business constraint enforcement: A university cannot have more than one assigned University Admin.
 */
test('creating a university admin fails when university already has one', function () {
    /** @var \Tests\TestCase $this */

    // Seed an existing University Admin for a specific university
    $existingAdmin = UniversityAdmin::factory()->create();

    ($this->actingAsSuperAdmin)();

    // Attempt creating a second admin for the same university
    $this->postJson('/api/v1/university-admins', [
        'name' => 'Second Admin',
        'email' => 'second-admin@example.com',
        'password' => 'password123',
        'university_id' => $existingAdmin->university_id,
    ])->assertStatus(422)
      ->assertJsonValidationErrors('university_id');
});

/**
 * Ensure creation request validates required fields before processing.
 */
test('creating a university admin requires valid data', function () {
    /** @var \Tests\TestCase $this */

    ($this->actingAsSuperAdmin)();

    // Submit empty payload and assert HTTP 422 Unprocessable Entity
    $this->postJson('/api/v1/university-admins', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password', 'university_id']);
});

/**
 * Ensure Super Admin can view specific University Admin details by ID.
 */
test('super admin can view a university admin', function () {
    /** @var \Tests\TestCase $this */

    $admin = UniversityAdmin::factory()->create();

    ($this->actingAsSuperAdmin)();

    $this->getJson("/api/v1/university-admins/{$admin->id}")
        ->assertOk()
        ->assertJson(['status' => 'success'])
        ->assertJsonPath('data.id', $admin->id);
});

/**
 * Ensure Super Admin can update an existing University Admin's details.
 */
test('super admin can update a university admin', function () {
    /** @var \Tests\TestCase $this */

    $admin = UniversityAdmin::factory()->create();

    ($this->actingAsSuperAdmin)();

    // Issue PUT request to modify name
    $this->putJson("/api/v1/university-admins/{$admin->id}", [
        'name' => 'Updated Name',
    ])->assertOk()
      ->assertJson(['status' => 'success'])
      ->assertJsonPath('data.name', 'Updated Name');

    // Verify underlying user profile update in database
    $this->assertDatabaseHas('users', [
        'id' => $admin->user_id,
        'name' => 'Updated Name',
    ]);
});

/**
 * Ensure unique email constraint is enforced during account updates.
 */
test('updating a university admin rejects a duplicate email', function () {
    /** @var \Tests\TestCase $this */

    $admin = UniversityAdmin::factory()->create();
    $otherUser = User::factory()->create();

    ($this->actingAsSuperAdmin)();

    // Attempt setting an email already owned by another user
    $this->putJson("/api/v1/university-admins/{$admin->id}", [
        'email' => $otherUser->email,
    ])->assertStatus(422)
      ->assertJsonValidationErrors('email');
});