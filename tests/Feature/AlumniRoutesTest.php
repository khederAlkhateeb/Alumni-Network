<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\Major;
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
    }

    private function createAlumniUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('alumni');
        
        $major = Major::factory()->create();
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
        Role::findOrCreate('student', 'api');
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('student');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/alumni')->assertStatus(403);
    }

    public function test_alumni_can_list_alumni_profiles(): void
    {
        $user = $this->createAlumniUser();
        Sanctum::actingAs($user);

        // Create another alumni
        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $response = $this->getJson('/api/v1/alumni');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'graduation_year',
                        'current_job_title',
                        'current_company',
                        'city',
                        'country',
                        'is_open_to_mentor',
                        'major',
                        'university',
                        'completion_percentage'
                    ]
                ],
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

        $profile = $user->alumniProfile->refresh();
        $this->assertEquals('Senior Engineer', $profile->current_job_title);
        $this->assertEquals('Google', $profile->current_company);
    }

    public function test_alumni_can_toggle_mentor_status(): void
    {
        $user = $this->createAlumniUser();
        Sanctum::actingAs($user);

        $initialStatus = $user->alumniProfile->is_open_to_mentor;

        $response = $this->postJson('/api/v1/alumni/me/toggle-mentor');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $user->alumniProfile->refresh();
        $this->assertNotEquals($initialStatus, $user->alumniProfile->is_open_to_mentor);
    }

    public function test_alumni_can_view_another_alumni_profile(): void
    {
        $user = $this->createAlumniUser();
        Sanctum::actingAs($user);

        $otherUser = User::factory()->create(['is_active' => true]);
        $otherUser->assignRole('alumni');
        $otherProfile = AlumniProfile::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'active'
        ]);

        $response = $this->getJson("/api/v1/alumni/{$otherProfile->id}");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', $otherProfile->id);
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
    }
}
