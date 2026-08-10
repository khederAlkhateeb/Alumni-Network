<?php

namespace Tests\Feature;

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
use Tests\TestCase;

/**
 * Class RegistrationManagementControllerTest
 * 
 * Functional/Feature test suite covering registration approval workflows, rejection logic,
 * multi-tenant university isolation, asynchronous job dispatching, and role-based access control (RBAC).
 */
class RegistrationManagementControllerTest extends TestCase
{
    // Resets the database state after each test method runs to guarantee isolation
    use RefreshDatabase;

    /**
     * Primary entities used across the test suite for university administration.
     */
    private University $university;
    private User $uniAdmin;
    private Major $major;

    /**
     * Set up the initial test environment before each test method runs.
     */
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /**
     * Helper method to generate an inactive Alumni user with a pending profile.
     *
     * @return User
     */
    private function pendingAlumniUser(): User
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('alumni');
        AlumniProfile::factory()->for($this->major)->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        return $user;
    }

    // =========================================================================
    // Registration Management Tests
    // =========================================================================

    /**
     * @test
     * Ensure University Admin can approve pending alumni registrations within their university,
     * updating status records and dispatching the approval notification job.
     */
    public function test_uni_admin_can_approve_a_pending_alumni_registration(): void
    {
        // Intercept queue jobs to test asynchronous dispatching
        Queue::fake();

        // Create target pending alumni user
        $targetUser = $this->pendingAlumniUser();

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
    }

    /**
     * @test
     * Ensure multi-tenant security prevents a University Admin from approving 
     * a registration belonging to a different university branch.
     */
    public function test_uni_admin_cannot_approve_a_user_from_another_university(): void
    {
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
    }

    /**
     * @test
     * Ensure non-administrative users (e.g., standard Alumni) cannot trigger approval endpoints.
     */
    public function test_alumni_role_cannot_approve_registrations(): void
    {
        // Authenticate as regular active alumni user
        $alumniUser = User::factory()->create(['is_active' => true]);
        $alumniUser->assignRole('alumni');

        $targetUser = $this->pendingAlumniUser();

        Sanctum::actingAs($alumniUser, ['*']);

        // Attempt restricted action and assert HTTP 403 Forbidden
        $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/approve")
            ->assertStatus(403);
    }

    /**
     * @test
     * Ensure University Admin can reject a pending alumni registration, 
     * setting the profile status to suspended while keeping the user inactive.
     */
    public function test_uni_admin_can_reject_a_pending_alumni_registration(): void
    {
        $targetUser = $this->pendingAlumniUser();

        Sanctum::actingAs($this->uniAdmin, ['*']);

        // Issue POST request to reject the registration
        $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/reject")
            ->assertOk()
            ->assertJson(['status' => 'success']);

        // Assert database state reflects rejection/suspension status
        $this->assertDatabaseHas('users', ['id' => $targetUser->id, 'is_active' => false]);
        $this->assertDatabaseHas('alumni_profiles', ['user_id' => $targetUser->id, 'status' => 'suspended']);
    }

    /**
     * @test
     * Ensure the pending registrations endpoint correctly filters records,
     * returning only pending alumni and students belonging to the specified university tenant.
     */
    public function test_pending_lists_only_this_universitys_pending_alumni_and_students(): void
    {
        // Create 1 pending alumni for this university
        $this->pendingAlumniUser();

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
    }

    /**
     * @test
     * Ensure unauthenticated guests are denied access to the pending registrations endpoint with HTTP 401.
     */
    public function test_guest_cannot_view_pending_registrations(): void
    {
        $this->getJson("/api/v1/uni_admin/universities/{$this->university->id}/pending-registrations")
            ->assertStatus(401);
    }
}