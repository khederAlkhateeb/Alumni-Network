<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MentorshipRequestRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private User $mentorUser;
    private MentorshipProgram $program;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('student', 'api');
        Role::findOrCreate('alumni', 'api');

        $faculty = Faculty::factory()->create();
        $major = Major::factory()->create(['faculty_id' => $faculty->id]);

        $this->studentUser = User::factory()->create(['is_active' => true]);
        $this->studentUser->assignRole('student');
        StudentProfile::factory()->create([
            'user_id' => $this->studentUser->id,
            'major_id' => $major->id,
            'status' => 'active',
        ]);

        $this->mentorUser = User::factory()->create(['is_active' => true]);
        $this->mentorUser->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $this->mentorUser->id,
            'major_id' => $major->id,
            'status' => 'active',
            'is_open_to_mentor' => true,
        ]);

        $this->program = MentorshipProgram::factory()->create([
            'university_id' => $this->studentUser->studentProfile->major->faculty->university_id,
            'title' => 'Tech Mentorship 2026',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_mentorship_endpoints(): void
    {
        $this->getJson('/api/v1/mentors')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/incoming')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/outgoing')->assertStatus(401);
    }

    public function test_student_can_list_available_mentors(): void
    {
        // Create a mentorship request so that the mentor has an associated program_id in the database,
        // avoiding a null program_id type error in AvailableMentorResource.
        MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $this->studentUser->id,
            'status' => 'pending',
            'intro_message' => 'Setup message',
        ]);

        Sanctum::actingAs($this->studentUser);

        $response = $this->getJson('/api/v1/mentors');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'program_id',
                        'is_available',
                    ]
                ]
            ]);
    }

    public function test_student_can_send_mentorship_request(): void
    {
        Sanctum::actingAs($this->studentUser);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'intro_message' => 'Hello, I want to learn web development.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request submitted successfully.');

        $this->assertDatabaseHas('mentorship_requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $this->studentUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_mentor_can_view_incoming_requests_and_accept_them(): void
    {
        $req = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $this->studentUser->id,
            'intro_message' => 'Intro message',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser);

        $incomingResponse = $this->getJson('/api/v1/mentorship-requests/incoming');
        $incomingResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');

        $acceptResponse = $this->postJson("/api/v1/mentorship-requests/{$req->id}/accept");
        $acceptResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request accepted successfully.');

        $this->assertEquals('accepted', $req->refresh()->status->value);
    }

    public function test_mentor_can_reject_request(): void
    {
        $req = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $this->studentUser->id,
            'intro_message' => 'Intro message',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser);

        $rejectResponse = $this->postJson("/api/v1/mentorship-requests/{$req->id}/reject");
        $rejectResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request rejected successfully.');

        $this->assertEquals('rejected', $req->refresh()->status->value);
    }
}
