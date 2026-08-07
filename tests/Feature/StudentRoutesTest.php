<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Major;
use App\Models\StudentProfile;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentRoutesTest extends TestCase
{
    use RefreshDatabase;

    private University $university;

    protected function setUp(): void
    {
        parent::setUp();
        $this->university = University::factory()->create();
    }

    private function createStudentUser(University $university): User
    {
        $user = User::factory()->create(['is_active' => true]);
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

        $response = $this->putJson('/api/v1/students/me', [
            'enrollment_number' => 'ENR-9999',
            'enrollment_year' => 2021,
            'expected_graduation_year' => 2025,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.enrollment_number', 'ENR-9999');

        $this->assertEquals('ENR-9999', $user->studentProfile->refresh()->enrollment_number);
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
}
