<?php

// Import Models required for managing registration workflows and entity relations
use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;

// Import Queue Jobs and System Seeders
use App\Jobs\SendApproveNotificationJob;
use Database\Seeders\RoleAndPermissionSeeder;

// Import Laravel Testing utilities
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

// Resets the database state after each test method runs to guarantee isolation
uses(RefreshDatabase::class);

/**
 * Set up the initial test environment before each test method runs.
 */
beforeEach(function () {
    /** @var \Tests\TestCase $this */

    // Seed default system roles and permissions via dedicated seeder
    $this->seed(RoleAndPermissionSeeder::class);

    // Build university structural hierarchy (University -> Faculty -> Major)
    $this->university = University::factory()->create();
    $faculty = Faculty::factory()->for($this->university)->create();
    $this->major = Major::factory()->for($faculty)->create();

    // Create a active University Admin bound specifically to $this->university
    $this->uniAdmin = User::factory()->create(['is_active' => true]);
    $this->uniAdmin->assignRole('uni_admin');
    $this->uniAdmin->universityAdmin()->create([
        'university_id' => $this->university->id,
    ]);

    /**
     * Helper closure to generate an inactive Alumni user with a pending profile.
     *
     * @return User
     */
    $this->pendingAlumniUser = function (): User {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('alumni');
        AlumniProfile::factory()->for($this->major)->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return $user;
    };
});

// =========================================================================
// Registration Management Tests
// =========================================================================

/**
 * Ensure University Admin can approve pending alumni registrations within their university,
 * updating status records and dispatching the approval notification job.
 */
test('uni admin can approve a pending alumni registration', function () {
    /** @var \Tests\TestCase $this */

    // Intercept queue jobs to test asynchronous dispatching
    Queue::fake();

    // Create target pending alumni user
    $targetUser = ($this->pendingAlumniUser)();

    // Authenticate as authorized University Admin
    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue POST request to approve the registration
    $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/approve")
        ->assertOk()
        ->assertJson(['status' => 'success']);

    // Assert database updates for user activation and profile status
    $this->assertDatabaseHas('users', ['id' => $targetUser->id, 'is_active' => true]);
    $this->assertDatabaseHas('alumni_profiles', ['user_id' => $targetUser->id, 'status' => 'active']);

    // Assert notification job was dispatched to the queue
    Queue::assertPushed(SendApproveNotificationJob::class);
});

/**
 * Ensure multi-tenant security prevents a University Admin from approving
 * a registration belonging to a different university branch.
 */
test('uni admin cannot approve a user from another university', function () {
    /** @var \Tests\TestCase $this */

    // Create an unmanaged external university, faculty, major, and target pending user
    $otherUniversity = University::factory()->create();
    $otherFaculty = Faculty::factory()->for($otherUniversity)->create();
    $otherMajor = Major::factory()->for($otherFaculty)->create();

    $targetUser = User::factory()->create(['is_active' => false]);
    $targetUser->assignRole('alumni');
    AlumniProfile::factory()->for($otherMajor)->create([
        'user_id' => $targetUser->id,
        'status' => 'pending',
    ]);

    // Authenticate as university admin belonging to a different university scope
    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Attempt approval across tenant boundaries
    $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/approve")
        ->assertStatus(403);

    // Verify target user remains inactive in the database
    $this->assertDatabaseHas('users', ['id' => $targetUser->id, 'is_active' => false]);
});

/**
 * Ensure non-administrative users (e.g., standard Alumni) cannot trigger approval endpoints.
 */
test('alumni role cannot approve registrations', function () {
    /** @var \Tests\TestCase $this */

    // Authenticate as regular active alumni user
    $alumniUser = User::factory()->create(['is_active' => true]);
    $alumniUser->assignRole('alumni');

    $targetUser = ($this->pendingAlumniUser)();

    Sanctum::actingAs($alumniUser, ['*']);

    // Attempt restricted action and assert HTTP 403 Forbidden
    $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/approve")
        ->assertStatus(403);
});

/**
 * Ensure University Admin can reject a pending alumni registration,
 * setting the profile status to suspended while keeping the user inactive.
 */
test('uni admin can reject a pending alumni registration', function () {
    /** @var \Tests\TestCase $this */

    $targetUser = ($this->pendingAlumniUser)();

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue POST request to reject the registration
    $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/reject")
        ->assertOk()
        ->assertJson(['status' => 'success']);

    // Assert database state reflects rejection/suspension status
    $this->assertDatabaseHas('users', ['id' => $targetUser->id, 'is_active' => false]);
    $this->assertDatabaseHas('alumni_profiles', ['user_id' => $targetUser->id, 'status' => 'suspended']);
});

/**
 * Ensure the pending registrations endpoint correctly filters records,
 * returning only pending alumni and students belonging to the specified university tenant.
 */
test('pending lists only this universitys pending alumni and students', function () {
    /** @var \Tests\TestCase $this */

    // Create 1 pending alumni for this university
    ($this->pendingAlumniUser)();

    // Create 1 pending student for this university
    $studentUser = User::factory()->create(['is_active' => false]);
    $studentUser->assignRole('student');
    StudentProfile::factory()->for($this->major)->create([
        'user_id' => $studentUser->id,
        'status' => 'pending',
    ]);

    // Create 1 pending alumni for an external university (should be excluded from response)
    $otherUniversity = University::factory()->create();
    $otherFaculty = Faculty::factory()->for($otherUniversity)->create();
    $otherMajor = Major::factory()->for($otherFaculty)->create();
    $otherUser = User::factory()->create(['is_active' => false]);
    AlumniProfile::factory()->for($otherMajor)->create([
        'user_id' => $otherUser->id,
        'status' => 'pending',
    ]);

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request to retrieve pending list
    $response = $this->getJson("/api/v1/uni_admin/universities/{$this->university->id}/pending-registrations")
        ->assertOk();

    // Assert response contains exactly 1 alumni and 1 student for this university scope
    $response->assertJsonCount(1, 'data.alumni');
    $response->assertJsonCount(1, 'data.students');
});

/**
 * Ensure unauthenticated guests are denied access to the pending registrations endpoint with HTTP 401.
 */
test('guest cannot view pending registrations', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson("/api/v1/uni_admin/universities/{$this->university->id}/pending-registrations")
        ->assertStatus(401);
});