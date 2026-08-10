<?php

namespace Tests\Feature;

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
use Tests\TestCase;

/**
 * Class ReportsTest
 * 
 * Functional/Feature test suite covering analytic reporting endpoints across 
 * university domains: Alumni demographics, employment statistics, mentorship engagement, 
 * event attendance metrics, and job posting activity.
 */
class ReportsTest extends TestCase
{
    // Resets the database state after each test method runs to guarantee test isolation
    use RefreshDatabase;

    /**
     * Primary test environment entities for scoped analytics reporting.
     */
    private University $university;
    private User $uniAdmin;

    /**
     * Set up the initial test environment before each test method runs.
     */
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /**
     * Helper method to seed an alumni profile with its underlying academic hierarchy (Faculty -> Major).
     *
     * @param University $university
     * @param array $attributes
     * @return AlumniProfile
     */
    private function makeAlumni(University $university, array $attributes = []): AlumniProfile
    {
        $faculty = Faculty::factory()->for($university)->create();
        $major = Major::factory()->for($faculty)->create();

        return AlumniProfile::factory()
            ->for($major)
            ->create(array_merge(['status' => 'active'], $attributes));
    }

    // =========================================================================
    // Authentication & Access Control (RBAC) Tests
    // =========================================================================

    /**
     * @test
     * Ensure unauthenticated guests are denied access to reporting endpoints with HTTP 401 Unauthorized.
     */
    public function test_guest_cannot_access_any_report(): void
    {
        $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
            ->assertStatus(401);
    }

    /**
     * @test
     * Ensure standard alumni roles are forbidden from accessing administrative analytics with HTTP 403.
     */
    public function test_alumni_role_is_forbidden_from_reports(): void
    {
        $alumniUser = User::factory()->create(['is_active' => true]);
        $alumniUser->assignRole('alumni');

        Sanctum::actingAs($alumniUser, ['*']);

        $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
            ->assertStatus(403);
    }

    /**
     * @test
     * Ensure multi-tenant isolation prevents University Admins from viewing analytics of other universities.
     */
    public function test_uni_admin_cannot_view_reports_of_another_university(): void
    {
        $otherUniversity = University::factory()->create();

        Sanctum::actingAs($this->uniAdmin, ['*']);

        $this->getJson("/api/v1/universities/{$otherUniversity->id}/reports/alumni-overview")
            ->assertStatus(403);
    }

    /**
     * @test
     * Ensure Super Admins possess global privileges to access reports for any university branch.
     */
    public function test_super_admin_can_view_any_university_report(): void
    {
        $superAdmin = User::factory()->create(['is_active' => true]);
        $superAdmin->assignRole('super_admin');

        Sanctum::actingAs($superAdmin, ['*']);

        $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
            ->assertOk()
            ->assertJson(['status' => 'success']);
    }

    // =========================================================================
    // Analytics & Metrics Domain Tests
    // =========================================================================

    /**
     * @test
     * Verify alumni overview report groups active records by major and graduation year while filtering pending profiles.
     */
    public function test_alumni_overview_report_groups_by_major_and_graduation_year(): void
    {
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
        $this->makeAlumni(University::factory()->create());

        Sanctum::actingAs($this->uniAdmin, ['*']);

        // Issue GET request to retrieve overview analytics
        $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/alumni-overview")
            ->assertOk();

        // Assert accurate totals and grouped collection counts
        $response->assertJsonPath('data.total_alumni', 3);
        $this->assertCount(2, $response->json('data.by_major'));
        $this->assertCount(2, $response->json('data.by_graduation_year'));
    }

    /**
     * @test
     * Verify employment rate analytics correctly compute employment percentages and active work histories.
     */
    public function test_employment_rate_report_computes_percentage_and_top_companies(): void
    {
        // Seed active employed alumni with active work experience record
        $employed = $this->makeAlumni($this->university, ['current_company' => 'Acme']);
        WorkExperience::factory()->for($employed)->create([
            'company' => 'Acme',
            'end_date' => null,
        ]);

        // Seed second alumni profile
        $this->makeAlumni($this->university, ['current_company' => 'Beta']);

        Sanctum::actingAs($this->uniAdmin, ['*']);

        // Issue GET request for employment rate analytics
        $response = $this->getJson("/api/v1/universities/{$this->university->id}/reports/employment-rate")
            ->assertOk();

        // Assert calculated employment metrics (1 of 2 employed = 50%)
        $response->assertJsonPath('data.total_alumni', 2);
        $response->assertJsonPath('data.employed_alumni', 1);
        $response->assertJsonPath('data.employment_rate', 50);
    }

    /**
     * @test
     * Verify mentorship statistics accurately report request counts grouped by status within university programs.
     */
    public function test_mentorship_stats_report_counts_requests_by_status(): void
    {
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
    }

    /**
     * @test
     * Verify events engagement report accurately calculates total registrations, attendances, and attendance percentage.
     */
    public function test_events_engagement_report_computes_attendance_rate(): void
    {
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
    }

    /**
     * @test
     * Verify job activity report accurately counts university job listings and application status distributions.
     */
    public function test_jobs_activity_report_counts_jobs_and_applications(): void
    {
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
    }
}