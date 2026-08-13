<?php

use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @file StudentProfileFeatureTest.php
 *
 * Student Profiles Feature Test Suite.
 * Covers authentication guards, fetching authenticated student profiles,
 * updating student profile data, validation checks, and multi-tenant isolation rules.
 */

uses(RefreshDatabase::class);

/**
 * IDE Type Hinting for Pest Dynamic Properties
 *
 * @property University $university
 */

/*
|--------------------------------------------------------------------------
| Environment & Context Setup
|--------------------------------------------------------------------------
*/

/**
 * Setup test environment before each test execution.
 *
 * Seeds permissions and roles for both 'sanctum' and 'api' guards, assigns student permissions,
 * and initializes the primary university model instance.
 *
 * @return void
 */
beforeEach(function () {
    /** @var \Tests\TestCase $this */

    $guards = ['sanctum', 'api'];

    foreach ($guards as $guard) {
        $viewPerm = Permission::findOrCreate('view-student-profiles', $guard);
        $editPerm = Permission::findOrCreate('edit-own-profile', $guard);

        $role = Role::findOrCreate('student', $guard);
        $role->givePermissionTo([$viewPerm, $editPerm]);
    }

    test()->university = University::factory()->create();
});

/**
 * Helper function to create a fully hydrated Student User.
 *
 * Generates the relational academic hierarchy (Faculty -> Major) linked to the given university
 * and attaches an active StudentProfile instance to the user.
 *
 * @param University $university The university instance to associate the student with.
 * @return User User instance with refreshed profile and role relations.
 */
function createStudentUser(University $university): User
{
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $user->assignRole('student');

    $faculty = Faculty::factory()->create(['university_id' => $university->id]);
    $major   = Major::factory()->create(['faculty_id' => $faculty->id]);

    StudentProfile::factory()->create([
        'user_id'  => $user->id,
        'major_id' => $major->id,
        'status'   => 'active',
    ]);

    return $user->fresh(['studentProfile', 'roles', 'permissions']);
}

/*
|--------------------------------------------------------------------------
| Student Profiles Test Suite
|--------------------------------------------------------------------------
*/

/**
 * Authentication and access control tests for student endpoints.
 */
describe('Authentication Guards for Student Endpoints', function () {

    /**
     * Test that unauthenticated guest requests are denied access to student endpoints.
     *
     * @test
     * @expectedStatus 401 Unauthorized
     */
    it('denies access to any student profile operation for unauthenticated guests', function () {
        $this->getJson('/api/v1/students/me')->assertStatus(401);
        $this->putJson('/api/v1/students/me', [])->assertStatus(401);
        $this->getJson('/api/v1/students/1')->assertStatus(401);
    });

});

/**
 * Personal profile retrieval tests (/me endpoint).
 */
describe('Retrieving Authenticated Student Profile (/me)', function () {

    /**
     * Test fetching personal profile data for the authenticated student.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('returns the full profile data belonging strictly to the currently authenticated student', function () {
        $user = createStudentUser($this->university);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/students/me')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'My Profile get successfully.')
            ->assertJsonPath('data.id', $user->studentProfile->id)
            ->assertJsonPath('data.email', $user->email);
    });

});

/**
 * Profile updating and data validation tests.
 */
describe('Updating Authenticated Student Profile Information', function () {

    /**
     * Test updating valid academic and enrollment fields on the student profile.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('successfully updates and persists valid academic and enrollment profile fields', function () {
        $user = createStudentUser($this->university);

        Sanctum::actingAs($user, ['*']);

        $payload = [
            'enrollment_number'        => 'ENR-9999',
            'enrollment_year'          => 2021,
            'expected_graduation_year' => 2025,
        ];

        $this->putJson('/api/v1/students/me', $payload)
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.enrollment_number', 'ENR-9999');

        $this->assertDatabaseHas('student_profiles', [
            'user_id'                  => $user->id,
            'enrollment_number'        => 'ENR-9999',
            'enrollment_year'          => 2021,
            'expected_graduation_year' => 2025,
        ]);
    });

    /**
     * Test validation failure when updating fields with invalid data types.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('rejects update requests and returns validation errors when data types are invalid', function () {
        $user = createStudentUser($this->university);

        Sanctum::actingAs($user, ['*']);

        $this->putJson('/api/v1/students/me', [
            'enrollment_year' => 'not-a-number',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['enrollment_year']);
    });

});

/**
 * Peer profile viewing and university-level multi-tenant isolation tests.
 */
describe('Public Student Profile Viewing & Multi-Tenant Isolation', function () {

    /**
     * Test that a student can view another student's profile within the same university scope.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows a student to view the profile of another peer within the same university', function () {
        $user         = createStudentUser($this->university);
        $otherStudent = createStudentUser($this->university);

        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/v1/students/{$otherStudent->studentProfile->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $otherStudent->studentProfile->id);
    });

    /**
     * Test multi-tenant security isolating student profile viewing across different universities.
     *
     * @test
     * @expectedStatus 403 Forbidden
     */
    it('strictly forbids viewing student profiles belonging to a different university scope', function () {
        $user            = createStudentUser($this->university);
        $otherUniversity = University::factory()->create();
        $otherStudent    = createStudentUser($otherUniversity);

        Sanctum::actingAs($user, ['*']);

        $this->getJson("/api/v1/students/{$otherStudent->studentProfile->id}")
            ->assertForbidden();
    });

    /**
     * Test querying a non-existent student profile ID.
     *
     * @test
     * @expectedStatus 404 Not Found
     */
    it('returns a 404 response when attempting to fetch a student profile that does not exist', function () {
        $user = createStudentUser($this->university);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/students/999999')
            ->assertNotFound();
    });

});
