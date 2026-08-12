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

class StudentRoutesTest extends TestCase
{
    use RefreshDatabase;

    private University $university;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('student', 'api');
        $this->university = University::factory()->create();
    }

    private function createStudentUser(University $university): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('student');

        $faculty = Faculty::factory()->create(['university_id' => $university->id]);
        $major = Major::factory()->create(['faculty_id' => $faculty->id]);

        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'major_id' => $major->id,
        ]);

        return $user;
    }

    public function test_unauthenticated_user_cannot_access_students_endpoints(): void
    {
        $this->getJson('/api/v1/students/me')->assertStatus(401);
        $this->putJson('/api/v1/students/me', [])->assertStatus(401);
        $this->getJson('/api/v1/students/1')->assertStatus(401);
    }

    public function test_student_can_view_own_profile(): void
    {
        $user = $this->createStudentUser($this->university);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/students/me');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'My Profile get successfully.')
            ->assertJsonPath('data.id', $user->studentProfile->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_student_can_update_own_profile(): void
    {
        $user = $this->createStudentUser($this->university);

        Sanctum::actingAs($user);

        $payload = [
            'enrollment_number' => 'ENR-9999',
            'enrollment_year' => 2021,
            'expected_graduation_year' => 2025,
        ];

        $response = $this->putJson('/api/v1/students/me', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.enrollment_number', 'ENR-9999');

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $user->id,
            'enrollment_number' => 'ENR-9999',
            'enrollment_year' => 2021,
            'expected_graduation_year' => 2025,
        ]);
    }

    public function test_update_profile_fails_with_invalid_validation(): void
    {
        $user = $this->createStudentUser($this->university);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/students/me', [
            'enrollment_year' => 'not-a-number',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['enrollment_year']);
    }

    public function test_student_can_view_other_student_profile_in_same_university(): void
    {
        $user = $this->createStudentUser($this->university);
        $otherStudentUser = $this->createStudentUser($this->university);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/students/{$otherStudentUser->studentProfile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $otherStudentUser->studentProfile->id);
    }

    public function test_student_cannot_view_other_student_profile_in_different_university(): void
    {
        $user = $this->createStudentUser($this->university);

        $otherUniversity = University::factory()->create();
        $otherStudentUser = $this->createStudentUser($otherUniversity);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/students/{$otherStudentUser->studentProfile->id}");

        $response->assertStatus(403);
    }

    public function test_returns_404_when_viewing_non_existent_student_profile(): void
    {
        $user = $this->createStudentUser($this->university);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/students/999999');

        $response->assertStatus(404);
    }
}
