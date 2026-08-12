<?php

// Import Models required for setup and metric aggregation across reporting domains
use App\Models\AlumniProfile;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Major;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\University;
use App\Models\User;
use App\Models\WorkExperience;

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

    // Seed system roles and permissions via dedicated seeder
    $this->seed(RoleAndPermissionSeeder::class);

    // Initialize target university entity
    $this->university = University::factory()->create();

    // Create an active University Admin bound specifically to $this->university
    $this->uniAdmin = User::factory()->create(['is_active' => true]);
    $this->uniAdmin->assignRole('uni_admin');
    $this->uniAdmin->universityAdmin()->create([
        'university_id' => $this->university->id,
    ]);

    /**
     * Helper closure to seed an alumni profile with its underlying academic hierarchy (Faculty -> Major).
     *
     * @param University $university
     * @param array $attributes
     * @return AlumniProfile
     */
    $this->makeAlumni = function (University $university, array $attributes = []): AlumniProfile {
        $faculty = Faculty::factory()->for($university)->create();
        $major = Major::factory()->for($faculty)->create();

        return AlumniProfile::factory()
            ->for($major)
            ->create(array_merge(['status' => 'active'], $attributes));
    };
});

// =========================================================================
// Authentication & Access Control (RBAC) Tests
// =========================================================================

/**
 * Ensure unauthenticated guests are denied access to reporting endpoints with HTTP 401 Unauthorized.
 */
test('guest cannot access any report', function () {
    /** @var \Tests\TestCase $this */

    $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
        ->assertStatus(401);
});

/**
 * Ensure standard alumni roles are forbidden from accessing administrative analytics with HTTP 403.
 */
test('alumni role is forbidden from reports', function () {
    /** @var \Tests\TestCase $this */

    $alumniUser = User::factory()->create(['is_active' => true]);
    $alumniUser->assignRole('alumni');

    Sanctum::actingAs($alumniUser, ['*']);

    $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
        ->assertStatus(403);
});

/**
 * Ensure multi-tenant isolation prevents University Admins from viewing analytics of other universities.
 */
test('uni admin cannot view reports of another university', function () {
    /** @var \Tests\TestCase $this */

    $otherUniversity = University::factory()->create();

    Sanctum::actingAs($this->uniAdmin, ['*']);

    $this->getJson("/api/v1/universities/{$otherUniversity->id}/reports/alumni-overview")
        ->assertStatus(403);
});

/**
 * Ensure Super Admins possess global privileges to access reports for any university branch.
 */
test('super admin can view any university report', function () {
    /** @var \Tests\TestCase $this */

    $superAdmin = User::factory()->create(['is_active' => true]);
    $superAdmin->assignRole('super_admin');

    Sanctum::actingAs($superAdmin, ['*']);

    $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
        ->assertOk()
        ->assertJson(['status' => 'success']);
});

// =========================================================================
// Analytics & Metrics Domain Tests
// =========================================================================

/**
 * Verify alumni overview report groups active records by major and graduation year while filtering pending profiles.
 */
test('alumni overview report groups by major and graduation year', function () {
    /** @var \Tests\TestCase $this */

    // Build test faculty and majors within current university scope
    $faculty = Faculty::factory()->for($this->university)->create();
    $majorA = Major::factory()->for($faculty)->create();
    $majorB = Major::factory()->for($faculty)->create();

    // Seed active alumni for Major A (2022) and Major B (2023)
    AlumniProfile::factory()->for($majorA)->count(2)->create([
        'status' => 'active',
        'graduation_year' => 2022,
    ]);
    AlumniProfile::factory()->for($majorB)->create([
        'status' => 'active',
        'graduation_year' => 2023,
    ]);

    // Seed pending profile (should be excluded from active count)
    AlumniProfile::factory()->for($majorA)->create(['status' => 'pending']);

    // Seed alumni for an external university (should be excluded by tenant scope)
    ($this->makeAlumni)(University::factory()->create());

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request to retrieve overview analytics
    $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
        ->assertOk();

    // Assert accurate totals and grouped collection counts
    $response->assertJsonPath('data.total_alumni', 3);
    $this->assertCount(2, $response->json('data.by_major'));
    $this->assertCount(2, $response->json('data.by_graduation_year'));
});

/**
 * Verify employment rate analytics correctly compute employment percentages and active work histories.
 */
test('employment rate report computes percentage and top companies', function () {
    /** @var \Tests\TestCase $this */

    // Seed active employed alumni with active work experience record
    $employed = ($this->makeAlumni)($this->university, ['current_company' => 'Acme']);
    WorkExperience::factory()->for($employed)->create([
        'company' => 'Acme',
        'end_date' => null,
    ]);

    // Seed second alumni profile
    ($this->makeAlumni)($this->university, ['current_company' => 'Beta']);

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request for employment rate analytics
    $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/employment-rate")
        ->assertOk();

    // Assert calculated employment metrics (1 of 2 employed = 50%)
    $response->assertJsonPath('data.total_alumni', 2);
    $response->assertJsonPath('data.employed_alumni', 1);
    $response->assertJsonPath('data.employment_rate', 50);
});

/**
 * Verify mentorship statistics accurately report request counts grouped by status within university programs.
 */
test('mentorship stats report counts requests by status', function () {
    /** @var \Tests\TestCase $this */

    // Seed mentorship program for target university
    $program = MentorshipProgram::factory()->for($this->university)->create([
        'status' => 'active',
        'mentor_per_mentees_max' => 3,
    ]);

    // Seed requests with varying statuses
    MentorshipRequest::factory()->for($program, 'program')->create(['status' => 'accepted']);
    MentorshipRequest::factory()->for($program, 'program')->create(['status' => 'pending']);

    // Seed external mentorship program and request (should be excluded)
    $otherProgram = MentorshipProgram::factory()->create();
    MentorshipRequest::factory()->for($otherProgram, 'program')->create(['status' => 'accepted']);

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request for mentorship metrics
    $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/mentorship-stats")
        ->assertOk();

    // Assert status counts and program-specific metrics
    $response->assertJsonPath('data.total_programs', 1);
    $response->assertJsonPath('data.total_requests', 2);
    $response->assertJsonPath('data.requests_by_status.accepted', 1);
    $response->assertJsonPath('data.requests_by_status.pending', 1);
    $response->assertJsonPath('data.programs.0.accepted_requests', 1);
});

/**
 * Verify events engagement report accurately calculates total registrations, attendances, and attendance percentage.
 */
test('events engagement report computes attendance rate', function () {
    /** @var \Tests\TestCase $this */

    // Seed completed event for target university
    $event = Event::factory()->for($this->university)->create(['status' => 'completed']);

    // Seed 1 attended registration and 1 unattended registration
    EventRegistration::factory()->for($event)->for(User::factory(), 'user')->create(['attended_at' => now()]);
    EventRegistration::factory()->for($event)->for(User::factory(), 'user')->create(['attended_at' => null]);

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request for event engagement analytics
    $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/events-engagement")
        ->assertOk();

    // Assert calculated event attendance metrics (1 of 2 attended = 50%)
    $response->assertJsonPath('data.total_events', 1);
    $response->assertJsonPath('data.total_registrations', 2);
    $response->assertJsonPath('data.total_attendances', 1);
    $response->assertJsonPath('data.attendance_rate', 50);
});

/**
 * Verify job activity report accurately counts university job listings and application status distributions.
 */
test('jobs activity report counts jobs and applications', function () {
    /** @var \Tests\TestCase $this */

    // Seed job listing with submitted and shortlisted applications
    $job = JobListing::factory()->for($this->university)->create(['status' => 'active']);
    JobApplication::factory()->for($job, 'jobListing')->create(['status' => 'submitted']);
    JobApplication::factory()->for($job, 'jobListing')->create(['status' => 'shortlisted']);

    // Seed external job listing (should be excluded)
    JobListing::factory()->create(['status' => 'active']);

    Sanctum::actingAs($this->uniAdmin, ['*']);

    // Issue GET request for job activity analytics
    $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/jobs-activity")
        ->assertOk();

    // Assert totals and application status breakdown
    $response->assertJsonPath('data.total_jobs', 1);
    $response->assertJsonPath('data.total_applications', 2);
    $response->assertJsonPath('data.applications_by_status.submitted', 1);
    $response->assertJsonPath('data.applications_by_status.shortlisted', 1);
});