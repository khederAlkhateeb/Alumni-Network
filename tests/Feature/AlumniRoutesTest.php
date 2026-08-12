<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\University;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlumniRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('alumni', 'api');
        Role::findOrCreate('student', 'api');
    }

    private function createAlumniUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('alumni');

        $university = University::factory()->create();
        $faculty = Faculty::factory()->create(['university_id' => $university->id]);
        $major = Major::factory()->create(['faculty_id' => $faculty->id]);

        AlumniProfile::factory()->create([
            'user_id' => $user->id,
            'major_id' => $major->id,
            'status' => 'active'
        ]);

        return $user;
    }

    public function test_unauthenticated_user_cannot_access_alumni_endpoints(): void
    {
        $this->getJson('/api/v1/alumni')->assertStatus(401);
        $this->getJson('/api/v1/alumni/me')->assertStatus(401);
        $this->putJson('/api/v1/alumni/me/updateMe', [])->assertStatus(401);
        $this->postJson('/api/v1/alumni/me/toggle-mentor')->assertStatus(401);
        $this->postJson('/api/v1/alumni/me/complete-profile', [])->assertStatus(401);
    }

    public function test_non_alumni_user_cannot_access_alumni_endpoints(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('student');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(403)
                 ->assertJsonPath('status', false);
    }

    public function test_alumni_can_list_alumni_profiles(): void
    {
        $user = $this->createAlumniUser();
        $this->createAlumniUser(); 

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonStructure([
                     'status',
                     'message',
                     'data',
                     'meta'
                 ]);
    }

    public function test_alumni_can_view_their_own_profile(): void
    {
        $user = $this->createAlumniUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/alumni/me');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('message', 'Your profile retrieved successfully.');
    }

    public function test_alumni_can_update_their_own_profile(): void
    {
        $user = $this->createAlumniUser();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/alumni/me/updateMe', [
            'bio' => 'Updated bio description',
            'city' => 'New York',
            'current_job_title' => 'Senior Engineer',
            'current_company' => 'Google'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.bio', 'Updated bio description')
                 ->assertJsonPath('data.city', 'New York');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $user->id,
            'current_job_title' => 'Senior Engineer',
            'current_company' => 'Google',
            'city' => 'New York',
        ]);
    }

    public function test_alumni_can_toggle_mentor_status(): void
    {
        $user = $this->createAlumniUser();
        $initialStatus = $user->alumniProfile->is_open_to_mentor;

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/alumni/me/toggle-mentor');

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success');

        $user->alumniProfile->refresh();
        $this->assertNotEquals($initialStatus, $user->alumniProfile->is_open_to_mentor);
    }

    public function test_alumni_can_view_another_alumni_profile(): void
    {
        $user = $this->createAlumniUser();

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('alumni');

        $university = University::factory()->create();
        $faculty = Faculty::factory()->create(['university_id' => $university->id]);
        $major = Major::factory()->create(['faculty_id' => $faculty->id]);

        $otherProfile = AlumniProfile::factory()->create([
            'user_id' => $otherUser->id,
            'major_id' => $major->id,
            'status' => 'active'
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/alumni/{$otherProfile->id}");

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.id', $otherProfile->id);
    }

    public function test_returns_404_when_viewing_non_existent_alumni_profile(): void
    {
        $user = $this->createAlumniUser();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/alumni/999999');

        $response->assertStatus(404)
                 ->assertJsonPath('status', false);
    }

    public function test_alumni_can_complete_profile(): void
    {
        $user = $this->createAlumniUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/alumni/me/complete-profile', [
            'bio' => 'Fully completed bio',
            'graduation_year' => 2020,
            'current_job_title' => 'Architect'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('data.bio', 'Fully completed bio');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $user->id,
            'graduation_year' => 2020,
            'current_job_title' => 'Architect',
        ]);
    }

    public function test_complete_profile_fails_with_invalid_data(): void
    {
        $user = $this->createAlumniUser();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/alumni/me/complete-profile', [
            'graduation_year' => 'invalid-year-format',
        ]);

        $response->assertStatus(422)
                 ->assertJsonPath('status', false)
                 ->assertJsonValidationErrors(['graduation_year']);
    }
}
