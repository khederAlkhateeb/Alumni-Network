<?php

namespace Tests\Feature;

use App\Enums\MentorshipRequestStatus;
use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Major;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UniversityRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'api');
        Role::findOrCreate('uni_admin', 'api');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a university with a faculty and major attached to it.
     * Returns ['university', 'faculty', 'major'].
     */
    private function createUniversityWithMajor(): array
    {
        $university = University::factory()->create();
        $faculty = Faculty::factory()->create(['university_id' => $university->id]);
        $major = Major::factory()->create(['faculty_id' => $faculty->id]);

        return compact('university', 'faculty', 'major');
    }

    /**
     * Create a super_admin user authenticated via Sanctum.
     */
    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create a uni_admin user bound to $university, authenticated via Sanctum.
     */
    private function actingAsUniAdmin(University $university): User
    {
        $user = User::factory()->create();
        $user->assignRole('uni_admin');
        UniversityAdmin::create([
            'user_id' => $user->id,
            'university_id' => $university->id,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_unauthenticated_user_can_list_universities()
    {
        University::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/universities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_filters_by_name()
    {
        University::factory()->create(['name' => 'Harvard University']);
        University::factory()->create(['name' => 'MIT']);

        $response = $this->getJson('/api/v1/universities?name=Harvard');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Harvard University');
    }

    public function test_index_filters_by_country()
    {
        University::factory()->create(['country' => 'United States']);
        University::factory()->create(['country' => 'United Kingdom']);

        $response = $this->getJson('/api/v1/universities?country=United States');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_unauthenticated_user_cannot_create_university()
    {
        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_super_admin_cannot_create_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'New University',
            'country' => 'United States',
            'website' => 'https://newuni.edu',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University created successfully.')
            ->assertJsonPath('data.name', 'New University')
            ->assertJsonPath('data.country', 'United States');

        $this->assertDatabaseHas('universities', [
            'name' => 'New University',
            'country' => 'United States',
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'country']);
    }

    public function test_store_validates_unique_name()
    {
        University::factory()->create(['name' => 'Existing University']);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/universities', [
            'name' => 'Existing University',
            'country' => 'United States',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_unauthenticated_user_cannot_view_university()
    {
        $university = University::factory()->create();

        $response = $this->getJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_view_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->getJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $university->id)
            ->assertJsonPath('data.name', $university->name);
    }

    public function test_unauthenticated_user_cannot_update_university()
    {
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(401);
    }

    public function test_non_authorized_user_cannot_update_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->putJson("/api/v1/universities/{$university->id}", [
            'name' => 'Updated University Name',
            'country' => 'Canada',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University updated successfully.')
            ->assertJsonPath('data.name', 'Updated University Name')
            ->assertJsonPath('data.country', 'Canada');
    }

    public function test_unauthenticated_user_cannot_delete_university()
    {
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(401);
    }

    public function test_non_super_admin_cannot_delete_university()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(403);
    }

    public function test_super_admin_can_delete_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);
        $university = University::factory()->create();

        $response = $this->deleteJson("/api/v1/universities/{$university->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'University deleted successfully.');

        $this->assertDatabaseMissing('universities', ['id' => $university->id]);
    }

    public function test_returns_404_for_nonexistent_university()
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/universities/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // University Stats endpoint  GET /{university}/stats
    // =========================================================================

    // ---- Authorization -------------------------------------------------------

    /** Unauthenticated requests must be rejected. */
    public function test_stats_requires_authentication(): void
    {
        $university = University::factory()->create();

        $this->getJson("/api/v1/{$university->id}/stats")
            ->assertStatus(401);
    }

    /** A plain authenticated user (no role) must be denied. */
    public function test_stats_forbidden_for_regular_user(): void
    {
        $university = University::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/{$university->id}/stats")
            ->assertStatus(403); 
    }

    /** A uni_admin targeting a different university must be denied. */
    public function test_stats_forbidden_for_uni_admin_of_different_university(): void
    {
        ['university' => $ownUniversity] = $this->createUniversityWithMajor();
        $otherUniversity = University::factory()->create();

        $this->actingAsUniAdmin($ownUniversity);

        $this->getJson("/api/v1/{$otherUniversity->id}/stats")
            ->assertStatus(403);
    }

    /** Super admin can access stats for any university. */
    public function test_stats_accessible_by_super_admin(): void
    {
        ['university' => $university] = $this->createUniversityWithMajor();
        $this->actingAsSuperAdmin();

        $this->getJson("/api/v1/{$university->id}/stats")
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    /** Uni admin can access stats for their own university. */
    public function test_stats_accessible_by_own_uni_admin(): void
    {
        ['university' => $university] = $this->createUniversityWithMajor();
        $this->actingAsUniAdmin($university);

        $this->getJson("/api/v1/{$university->id}/stats")
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }

    // ---- Response structure --------------------------------------------------

    /** The response contains every top-level KPI group. */
    public function test_stats_response_has_expected_structure(): void
    {
        ['university' => $university] = $this->createUniversityWithMajor();
        $this->actingAsSuperAdmin();

        $this->getJson("/api/v1/{$university->id}/stats")
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'university' => ['id', 'name'],
                    'overview' => ['total_alumni', 'total_students', 'pending_approvals'],
                    'employment_kpis' => ['employment_rate', 'open_job_postings', 'total_applications_submitted'],
                    'mentorship_kpis' => ['active_mentors', 'ongoing_mentorship_sessions', 'pending_mentorship_requests'],
                    'events_kpis' => ['upcoming_events', 'total_registrations_this_month'],
                    'community_kpis' => ['total_posts'],
                ],
            ]);
    }
}
