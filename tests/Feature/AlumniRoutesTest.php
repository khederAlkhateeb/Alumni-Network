<?php

use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @file AlumniFeatureTest.php
 *
 * Alumni Feature Test Suite.
 * Covers authentication, authorization guards, directory query actions,
 * and profile state mutations for the Alumni API domain.
 */

/*
|--------------------------------------------------------------------------
| Environment & Context Setup
|--------------------------------------------------------------------------
*/

uses(RefreshDatabase::class);

/**
 * Setup test environment before each test execution.
 *
 * Seeds required permissions and roles across multiple guards ('api' and 'sanctum'),
 * assigning default permissions to the 'alumni' role.
 *
 * @return void
 */
beforeEach(function () {
    $guards = ['api', 'sanctum'];

    foreach ($guards as $guard) {
        $viewPerm   = Permission::findOrCreate('view-alumni-profiles', $guard);
        $editPerm   = Permission::findOrCreate('edit-own-profile', $guard);
        $togglePerm = Permission::findOrCreate('toggle-mentor-status', $guard);

        $alumniRole = Role::findOrCreate('alumni', $guard);
        $alumniRole->givePermissionTo([$viewPerm, $editPerm, $togglePerm]);

        Role::findOrCreate('student', $guard);
    }
});

/**
 * Helper function to create a fully hydrated Alumni User.
 *
 * Generates the relational academic hierarchy (University -> Faculty -> Major)
 * and attaches an active AlumniProfile instance to the user.
 *
 * @return User User instance with refreshed profile and role relations.
 */
function createAlumniUser(): User
{
    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('alumni');

    $university = University::factory()->create();
    $faculty    = Faculty::factory()->create(['university_id' => $university->id]);
    $major      = Major::factory()->create(['faculty_id' => $faculty->id]);

    AlumniProfile::factory()->create([
        'user_id'  => $user->id,
        'major_id' => $major->id,
        'status'   => 'active',
    ]);

    return $user->fresh(['alumniProfile', 'roles', 'permissions']);
}

/*
|--------------------------------------------------------------------------
| Alumni Feature Test Suite
|--------------------------------------------------------------------------
*/

/**
 * Authentication and authorization access control tests.
 */
describe('Alumni API Authorization & Role Guards', function () {

    /**
     * Test that unauthenticated guest requests are denied access to protected alumni endpoints.
     *
     * @test
     * @expectedStatus 401 Unauthorized
     */
    it('denies unauthenticated guests from accessing any alumni profile endpoints', function () {
        $this->getJson('/api/v1/alumni')->assertStatus(401);
        $this->getJson('/api/v1/alumni/me')->assertStatus(401);
        $this->putJson('/api/v1/alumni/me/updateMe', [])->assertStatus(401);
        $this->postJson('/api/v1/alumni/me/toggle-mentor')->assertStatus(401);
        $this->postJson('/api/v1/alumni/me/complete-profile', [])->assertStatus(401);
    });

    /**
     * Test that authenticated users with 'student' role cannot access alumni resources.
     *
     * @test
     * @expectedStatus 403 Forbidden
     */
    it('forbids unauthorized student accounts from accessing alumni domain resources', function () {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('student');

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(403);
    });

});

/**
 * Directory retrieval and public/private profile querying tests.
 */
describe('Alumni Directory & Profile Query Actions', function () {

    /**
     * Test that an authorized alumnus can retrieve a paginated alumni directory.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows verified alumni to retrieve a paginated directory of alumni profiles', function () {
        $user = createAlumniUser();
        createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta',
            ]);
    });

    /**
     * Test that an authenticated alumnus can retrieve their own profile details.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows an authenticated alumnus to fetch their personal profile details', function () {
        $user = createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/alumni/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Your profile retrieved successfully.');
    });

    /**
     * Test that an alumnus can view another user's public alumni profile by profile ID.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows an alumnus to view another public alumni profile by identifier', function () {
        $user = createAlumniUser();

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('alumni');

        $university = University::factory()->create();
        $faculty    = Faculty::factory()->create(['university_id' => $university->id]);
        $major      = Major::factory()->create(['faculty_id' => $faculty->id]);

        $otherProfile = AlumniProfile::factory()->create([
            'user_id'  => $otherUser->id,
            'major_id' => $major->id,
            'status'   => 'active',
        ]);

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson("/api/v1/alumni/{$otherProfile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $otherProfile->id);
    });

    /**
     * Test that querying a non-existent alumni profile ID returns a 404 response.
     *
     * @test
     * @expectedStatus 404 Not Found
     */
    it('returns a 404 response when querying a non-existent alumni profile', function () {
        $user = createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->getJson('/api/v1/alumni/999999');

        $response->assertStatus(404)
            ->assertJsonPath('status', false);
    });

});

/**
 * Profile state mutation and onboarding workflow tests.
 */
describe('Alumni Profile State & Mutation Actions', function () {

    /**
     * Test updating employment details and basic profile information.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows alumni to update their current employment and profile information', function () {
        $user = createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->putJson('/api/v1/alumni/me/updateMe', [
            'bio'               => 'Updated bio description',
            'city'              => 'New York',
            'current_job_title' => 'Senior Engineer',
            'current_company'   => 'Google',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.bio', 'Updated bio description')
            ->assertJsonPath('data.city', 'New York');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id'           => $user->id,
            'current_job_title' => 'Senior Engineer',
            'current_company'   => 'Google',
            'city'              => 'New York',
        ]);
    });

    /**
     * Test toggling the mentorship availability status on the alumni profile.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows alumni to toggle their availability status for mentorship program', function () {
        $user          = createAlumniUser();
        $initialStatus = $user->alumniProfile->is_open_to_mentor;

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/alumni/me/toggle-mentor');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->alumniProfile->refresh();
        $this->assertNotEquals($initialStatus, $user->alumniProfile->is_open_to_mentor);
    });

    /**
     * Test submitting full onboarding profile completion data.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows alumni to submit complete onboarding profile details', function () {
        $user = createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/alumni/me/complete-profile', [
            'bio'               => 'Fully completed bio',
            'graduation_year'   => 2020,
            'current_job_title' => 'Architect',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.bio', 'Fully completed bio');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id'           => $user->id,
            'graduation_year'   => 2020,
            'current_job_title' => 'Architect',
        ]);
    });

    /**
     * Test validation rule enforcement during profile completion.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('fails profile completion when payload fails validation rules', function () {
        $user = createAlumniUser();

        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/v1/alumni/me/complete-profile', [
            'graduation_year' => 'invalid-year-format',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonValidationErrors(['graduation_year']);
    });

});
