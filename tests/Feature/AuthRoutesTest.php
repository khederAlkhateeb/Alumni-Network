<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\AlumniProfile;
use App\Models\University;
use App\Models\UniversityAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    private University $university;
    private Faculty $faculty;
    private Major $major;

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
            'status' => 'pending'
        ]);
    }

    public function test_user_cannot_login_when_pending(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@test.com',
            'password' => bcrypt('password123'),
            'is_active' => false,
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
    }

    public function test_user_can_login_when_active(): void
    {
        $user = User::factory()->create([
            'email' => 'active@test.com',
            'password' => 'password123',
            'is_active' => true,
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

    public function test_uni_admin_can_approve_pending_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('uni_admin');
        UniversityAdmin::create([
            'user_id' => $admin->id,
            'university_id' => $this->university->id,
        ]);

        $targetUser = User::factory()->create(['is_active' => false]);
        $targetUser->assignRole('student');
        $profile = StudentProfile::factory()->create([
            'user_id' => $targetUser->id,
            'major_id' => $this->major->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'User registration approved successfully.');

        $this->assertEquals(\App\Enums\ProfileStatus::ACTIVE, $profile->refresh()->status);
        $this->assertEquals(1, $targetUser->refresh()->is_active);
    }

    public function test_uni_admin_can_reject_pending_user(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('uni_admin');
        UniversityAdmin::create([
            'user_id' => $admin->id,
            'university_id' => $this->university->id,
        ]);

        $targetUser = User::factory()->create(['is_active' => false]);
        $targetUser->assignRole('student');
        $profile = StudentProfile::factory()->create([
            'user_id' => $targetUser->id,
            'major_id' => $this->major->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/uni_admin/universities/{$this->university->id}/registrations/{$targetUser->id}/reject");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'User registration rejected successfully.');

        $this->assertEquals(\App\Enums\ProfileStatus::SUSPENDED, $profile->refresh()->status);
        $this->assertEquals(0, $targetUser->refresh()->is_active);
    }
}
