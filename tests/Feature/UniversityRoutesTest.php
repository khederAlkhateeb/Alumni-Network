<?php

use App\Enums\MentorshipRequestStatus;
use App\Enums\PostVisibility;
use App\Enums\ProfileStatus;
use App\Models\AlumniProfile;
use App\Models\Event;
use App\Models\Faculty;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\Major;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\Post;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;


beforeEach(function () {
    Role::findOrCreate('super_admin', 'api');
    Role::findOrCreate('uni_admin', 'api');
});

// Helpers

/**
 * Create a University together with a Faculty and a Major so that
 * alumni/student profiles can be scoped to it via the
 * profile → major → faculty → university chain.
 *
 * @return array{university: University, faculty: Faculty, major: Major}
 */
function createUniversityWithMajor(): array
{
    $university = University::factory()->create();
    $faculty = Faculty::factory()->create(['university_id' => $university->id]);
    $major = Major::factory()->create(['faculty_id' => $faculty->id]);

    return compact('university', 'faculty', 'major');
}

/**
 * Create a super_admin user and authenticate them via Sanctum.
 * Super admins can manage all universities and view any stats.
 */
function actingAsSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    Sanctum::actingAs($user);

    return $user;
}

/**
 * Create a uni_admin user bound to the given university and authenticate
 * them via Sanctum.  Uni admins can only access their own university.
 */
function actingAsUniAdmin(University $university): User
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

// ===========================================================================
// GET /api/v1/universities  — public listing
// ===========================================================================

/**
 * The university index is public.
 * Any visitor (no token) should receive a paginated 200 response
 * with the correct JSON structure and total count.
 */
test('unauthenticated user can list universities', function () {
    University::factory()->count(3)->create();

    $this->getJson('/api/v1/universities')
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

/**
 * The ?name= query parameter should filter results by partial university name.
 * Only matching records should be returned.
 */
test('index filters by name', function () {
    University::factory()->create(['name' => 'SVU']);
    University::factory()->create(['name' => 'MIT']);

    $this->getJson('/api/v1/universities?name=SVU')
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'SVU');
});

/**
 * The ?country= query parameter should filter results by country.
 * Only universities in the specified country should be returned.
 */
test('index filters by country', function () {
    University::factory()->create(['country' => 'Syria']);
    University::factory()->create(['country' => 'UAE']);

    $this->getJson('/api/v1/universities?country=UAE')
        ->assertStatus(200)
        ->assertJsonPath('meta.total', 1);
});

// ===========================================================================
// POST /api/v1/universities  — create
// ===========================================================================

/**
 * Creating a university requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot create university', function () {
    $this->postJson('/api/v1/universities', [
        'name' => 'New University',
        'country' => 'United States',
    ])->assertStatus(401);
});

/**
 * Only super admins may create universities.
 * A regular authenticated user (no role) must receive 403 Forbidden.
 */
test('non super admin cannot create university', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/universities', [
        'name' => 'New University',
        'country' => 'United States',
    ])->assertStatus(403);
});

/**
 * A super admin can successfully create a university.
 * The response must be 201 with the created university's data,
 * and the record must be persisted to the database.
 * Note: logo is nullable — sending a URL string would fail file validation.
 */
test('super admin can create university', function () {
    actingAsSuperAdmin();

    $this->postJson('/api/v1/universities', [
        'name' => 'New University',
        'country' => 'United States',
        'website' => 'https://newuni.edu',
    ])
        ->assertStatus(201)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'University created successfully.')
        ->assertJsonPath('data.name', 'New University')
        ->assertJsonPath('data.country', 'United States')
        ->assertJsonStructure([
            'status',
            'message',
            'data' => ['id', 'name', 'country', 'website', 'logo', 'created_at', 'updated_at'],
        ]);

    expect(University::where('name', 'New University')->exists())->toBeTrue();
});

/**
 * The store endpoint validates that name and country are required.
 * Submitting an empty payload must return 422 with field-level errors.
 */
test('store validates required fields', function () {
    actingAsSuperAdmin();

    $this->postJson('/api/v1/universities', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'country']);
});

/**
 * University names must be unique across the system.
 * Attempting to create a duplicate name must return 422.
 */
test('store validates unique name', function () {
    University::factory()->create(['name' => 'Existing University']);
    actingAsSuperAdmin();

    $this->postJson('/api/v1/universities', [
        'name' => 'Existing University',
        'country' => 'United States',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// ===========================================================================
// GET /api/v1/universities/{university}  — show
// ===========================================================================

/**
 * Viewing a single university requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot view university', function () {
    $university = University::factory()->create();

    $this->getJson("/api/v1/universities/{$university->id}")->assertStatus(401);
});

/**
 * Any authenticated user may view a single university record.
 * The response must include the university's id and name.
 */
test('authenticated user can view university', function () {
    $university = University::factory()->create();
    actingAsSuperAdmin();

    $this->getJson("/api/v1/universities/{$university->id}")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $university->id)
        ->assertJsonPath('data.name', $university->name);
});

// ===========================================================================
// PUT /api/v1/universities/{university}  — update
// ===========================================================================

/**
 * Updating a university requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot update university', function () {
    $university = University::factory()->create();

    $this->putJson("/api/v1/universities/{$university->id}", ['name' => 'X'])
        ->assertStatus(401);
});

/**
 * The update() policy returns false for unauthorised users → 403 Forbidden.
 * A plain user with no role or ownership must be denied.
 */
test('non authorized user cannot update university', function () {
    Sanctum::actingAs(User::factory()->create());
    $university = University::factory()->create();

    $this->putJson("/api/v1/universities/{$university->id}", ['name' => 'X'])
        ->assertStatus(403);
});

/**
 * A super admin can update any university.
 * The response must reflect the new field values.
 */
test('super admin can update university', function () {
    $university = University::factory()->create();
    actingAsSuperAdmin();

    $this->putJson("/api/v1/universities/{$university->id}", [
        'name' => 'Updated University Name',
        'country' => 'Canada',
    ])
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'University updated successfully.')
        ->assertJsonPath('data.name', 'Updated University Name')
        ->assertJsonPath('data.country', 'Canada');
});

// ===========================================================================
// DELETE /api/v1/universities/{university}  — destroy
// ===========================================================================

/**
 * Deleting a university requires authentication.
 * An unauthenticated request must be rejected with 401.
 */
test('unauthenticated user cannot delete university', function () {
    $university = University::factory()->create();

    $this->deleteJson("/api/v1/universities/{$university->id}")->assertStatus(401);
});

/**
 * Only super admins may delete universities.
 * The delete() policy returns false for other users → 403 Forbidden.
 */
test('non super admin cannot delete university', function () {
    Sanctum::actingAs(User::factory()->create());
    $university = University::factory()->create();

    $this->deleteJson("/api/v1/universities/{$university->id}")->assertStatus(403);
});

/**
 * A super admin can hard-delete a university.
 * University does not use SoftDeletes, so the record must be gone from the DB.
 */
test('super admin can delete university', function () {
    $university = University::factory()->create();
    actingAsSuperAdmin();

    $this->deleteJson("/api/v1/universities/{$university->id}")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('message', 'University deleted successfully.');

    $this->assertDatabaseMissing('universities', ['id' => $university->id]);
});

/**
 * Requesting a university that does not exist must return 404.
 */
test('returns 404 for nonexistent university', function () {
    actingAsSuperAdmin();

    $this->getJson('/api/v1/universities/99999')->assertStatus(404);
});

// ===========================================================================
// GET /{university}/stats  — University stats
// ===========================================================================

// Authorization
/**
 * The stats endpoint requires a valid Sanctum token.
 * An unauthenticated request must be rejected with 401.
 */
test('stats requires authentication', function () {
    $university = University::factory()->create();

    $this->getJson("/api/v1/universities/{$university->id}/stats")->assertStatus(401);
});

/**
 * A plain authenticated user with no admin role must be denied.
 * The policy uses denyAsNotFound() but the exception handler maps all
 * AuthorizationException instances to 403 Forbidden.
 */
test('stats forbidden for regular user', function () {
    $university = University::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/universities/{$university->id}/stats")->assertStatus(403);
});

/**
 * A uni_admin may only view stats for their own university.
 * Targeting a different university must be denied with 403.
 */
test('stats forbidden for uni admin of different university', function () {
    ['university' => $ownUniversity] = createUniversityWithMajor();
    $otherUniversity = University::factory()->create();

    actingAsUniAdmin($ownUniversity);

    $this->getJson("/api/v1/universities/{$otherUniversity->id}/stats")->assertStatus(403);
});

/**
 * A super admin may access stats for any university in the system.
 */
test('stats accessible by super admin', function () {
    ['university' => $university] = createUniversityWithMajor();
    actingAsSuperAdmin();

    $this->getJson("/api/v1/universities/{$university->id}/stats")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');
});

/**
 * A uni_admin can access stats for the university they are assigned to.
 */
test('stats accessible by own uni admin', function () {
    ['university' => $university] = createUniversityWithMajor();
    actingAsUniAdmin($university);

    $this->getJson("/api/v1/universities/{$university->id}/stats")
        ->assertStatus(200)
        ->assertJsonPath('status', 'success');
});


// Response structure
/**
 * The stats response must contain all six top-level KPI groups:
 * university identity, overview, employment, mentorship, events, and community.
 */
test('stats response has expected structure', function () {
    ['university' => $university] = createUniversityWithMajor();
    actingAsSuperAdmin();

    $this->getJson("/api/v1/universities/{$university->id}/stats")
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
});
