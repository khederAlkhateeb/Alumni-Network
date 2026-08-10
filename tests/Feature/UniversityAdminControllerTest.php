<?php

namespace Tests\Feature;

// Import Models required for managing university administration entities and authentication
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;

// Import System Seeders
use Database\Seeders\RoleAndPermissionSeeder;

// Import Laravel Testing utilities
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Class UniversityAdminControllerTest
 * 
 * Functional/Feature test suite covering system administrator CRUD operations 
 * over University Administrator accounts (`UniversityAdmin`), role assignment, 
 * single-admin per university business rules, input validation, and RBAC security.
 */
class UniversityAdminControllerTest extends TestCase
{
    // Resets the database state after each test method runs to guarantee test isolation
    use RefreshDatabase;

    /**
     * Primary actor instance for managing global administration resources.
     */
    private User $superAdmin;

    /**
     * Set up the initial test environment before each test method runs.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Seed default system roles and permissions via dedicated seeder
        $this->seed(RoleAndPermissionSeeder::class);

        // Instantiate an active Super Admin with full management privileges
        $this->superAdmin = User::factory()->create(['is_active' => true]);
        $this->superAdmin->assignRole('super_admin');
    }

    /**
     * Helper method to authenticate the request as the Super Admin via Sanctum.
     */
    private function actingAsSuperAdmin(): void
    {
        Sanctum::actingAs($this->superAdmin, ['*']);
    }

    // =========================================================================
    // Access Control & Role Enforcement (RBAC) Tests
    // =========================================================================

    /**
     * @test
     * Ensure unauthenticated guests are denied access to University Admin management with HTTP 401.
     */
    public function test_guest_cannot_access_university_admins(): void
    {
        $this->getJson('/api/v1/university-admins')
             ->assertStatus(401);
    }

    /**
     * @test
     * Ensure scoped University Admins (`uni_admin`) are forbidden from managing or creating other University Admins.
     */
    public function test_uni_admin_is_forbidden_from_managing_university_admins(): void
    {
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
    }

    // =========================================================================
    // CRUD & Business Rule Validation Tests
    // =========================================================================

    /**
     * @test
     * Ensure Super Admin can retrieve a paginated listing of all registered University Admin entities.
     */
    public function test_super_admin_can_list_university_admins(): void
    {
        // Seed 3 University Admin records
        UniversityAdmin::factory()->count(3)->create();

        $this->actingAsSuperAdmin();

        // Issue GET request and verify paginated data count
        $this->getJson('/api/v1/university-admins')
            ->assertOk()
            ->assertJson(['status' => 'success'])
            ->assertJsonCount(3, 'data.data');
    }

    /**
     * @test
     * Ensure Super Admin can create a new University Admin, properly assigning the `uni_admin` role 
     * and persisting records in both `users` and `university_admins` tables.
     */
    public function test_super_admin_can_create_a_university_admin(): void
    {
        $university = University::factory()->create();

        $this->actingAsSuperAdmin();

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
    }

    /**
     * @test
     * Ensure business constraint enforcement: A university cannot have more than one assigned University Admin.
     */
    public function test_creating_a_university_admin_fails_when_university_already_has_one(): void
    {
        // Seed an existing University Admin for a specific university
        $existingAdmin = UniversityAdmin::factory()->create();

        $this->actingAsSuperAdmin();

        // Attempt creating a second admin for the same university
        $this->postJson('/api/v1/university-admins', [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => 'password123',
            'university_id' => $existingAdmin->university_id,
        ])->assertStatus(422)
          ->assertJsonValidationErrors('university_id');
    }

    /**
     * @test
     * Ensure creation request validates required fields before processing.
     */
    public function test_creating_a_university_admin_requires_valid_data(): void
    {
        $this->actingAsSuperAdmin();

        // Submit empty payload and assert HTTP 422 Unprocessable Entity
        $this->postJson('/api/v1/university-admins', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'university_id']);
    }

    /**
     * @test
     * Ensure Super Admin can view specific University Admin details by ID.
     */
    public function test_super_admin_can_view_a_university_admin(): void
    {
        $admin = UniversityAdmin::factory()->create();

        $this->actingAsSuperAdmin();

        $this->getJson("/api/v1/university-admins/{$admin->id}")
            ->assertOk()
            ->assertJson(['status' => 'success'])
            ->assertJsonPath('data.id', $admin->id);
    }

    /**
     * @test
     * Ensure Super Admin can update an existing University Admin's details.
     */
    public function test_super_admin_can_update_a_university_admin(): void
    {
        $admin = UniversityAdmin::factory()->create();

        $this->actingAsSuperAdmin();

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
    }

    /**
     * @test
     * Ensure unique email constraint is enforced during account updates.
     */
    public function test_updating_a_university_admin_rejects_a_duplicate_email(): void
    {
        $admin = UniversityAdmin::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAsSuperAdmin();

        // Attempt setting an email already owned by another user
        $this->putJson("/api/v1/university-admins/{$admin->id}", [
            'email' => $otherUser->email,
        ])->assertStatus(422)
          ->assertJsonValidationErrors('email');
    }
}