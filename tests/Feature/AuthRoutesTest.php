<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for the authentication endpoints: registration,
 * login (including the pending-account rejection rule), fetching
 * the authenticated profile, and logout.
 */
class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    private University $university;
    private Faculty $faculty;
    private Major $major;

    /**
     * Seed the roles used across these tests, plus a minimal academic
     * hierarchy (University -> Faculty -> Major) that registration
     * and profile creation depend on.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('uni_admin', 'api');
        Role::findOrCreate('student', 'api');
        Role::findOrCreate('alumni', 'api');

        $this->university = University::factory()->create();
        $this->faculty = Faculty::factory()->create(['university_id' => $this->university->id]);
        $this->major = Major::factory()->create(['faculty_id' => $this->faculty->id]);
    }

    /**
     * A new student registration succeeds, creates the User + StudentProfile,
     * and leaves the profile in 'pending' status awaiting admin approval —
     * per the spec's manual-approval registration flow.
     */
    public function test_user_can_register_as_student(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'student@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'university_id' => $this->university->id,
            'faculty_id' => $this->faculty->id,
            'major_id' => $this->major->id,
            'enrollment_number' => 'ENR-12345',
            'enrollment_year' => 2023,
            'expected_graduation_year' => 2027,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Registration successful. Awaiting approval.');

        $this->assertDatabaseHas('users', ['email' => 'student@test.com']);
        $this->assertDatabaseHas('student_profiles', [
            'enrollment_number' => 'ENR-12345',
            'status' => 'pending',
        ]);
    }

    /**
     * A user whose account is still pending approval cannot log in,
     * even with correct credentials — per spec: pending users cannot
     * interact with the platform at all.
     *
     * Note: the plain password is passed as-is (not pre-hashed with
     * bcrypt()) because the User model casts 'password' => 'hashed',
     * which hashes the value automatically on save. Manually hashing
     * it here first would double-hash it, making the stored hash not
     * match 'password123' at all — the login would then fail for the
     * wrong reason (bad credentials) instead of the actual pending-status
     * rule this test is meant to verify.
     */
    public function test_user_cannot_login_when_pending(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@test.com',
            'password' => 'password123',
            // بدون is_active => false هون، خليها default (على الأغلب true)
        ]);
        $user->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'major_id' => $this->major->id,
            'status' => 'pending',
        ]);
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'pending@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('errors.email.0', 'Your account is still pending approval.');
    }

    /**
     * A user whose account is active can log in successfully and
     * receives an auth token plus their user data.
     */
    public function test_user_can_login_when_active(): void
    {
        $user = User::factory()->create([
            'email' => 'active@test.com',
            'password' => 'password123',
//            'is_active' => true,
        ]);
        $user->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'major_id' => $this->major->id,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'active@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['status', 'message', 'data' => ['token', 'user']]);
    }

    /**
     * An authenticated user can fetch their own profile (/auth/me)
     * and subsequently log out successfully.
     */
    public function test_authenticated_user_can_get_profile_and_logout(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('student');
        Sanctum::actingAs($user);

        $meResponse = $this->getJson('/api/v1/auth/me');
        $meResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $logoutResponse = $this->postJson('/api/v1/auth/logout');
        $logoutResponse->assertStatus(200)
            ->assertJsonPath('status', 'success');
    }
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'studenttestcom',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'university_id' => $this->university->id,
            'faculty_id' => $this->faculty->id,
            'major_id' => $this->major->id,
            'enrollment_number' => 'ENR-12345',
            'enrollment_year' => 2023,
            'expected_graduation_year' => 2027,
            ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'is_active' => true,
        ]);
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
        $response->assertStatus(422);
    }
    public function test_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@test.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Fau Doe',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'university_id' => $this->university->id,
            'faculty_id' => $this->faculty->id,
            'major_id' => $this->major->id,
            'enrollment_number' => 'ENR-12345',
            'enrollment_year' => 2023,
            'expected_graduation_year' => 2027,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }
}
