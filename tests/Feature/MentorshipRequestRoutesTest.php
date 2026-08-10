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

/**
 * Feature tests for mentorship request routes.
 *
 * Covers authentication, active-profile access, request validation,
 * mentorship request ownership, program lifecycle, and mentor capacity.
 */
class MentorshipRequestRoutesTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private User $mentorUser;
    private MentorshipProgram $program;

    /**
     * Create the authenticated student, mentor, academic structure, and
     * active mentorship program shared by the route scenarios.
     */
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

    /**
     * Unauthenticated users must be rejected by all mentorship routes.
     */
    public function test_unauthenticated_user_cannot_access_mentorship_endpoints(): void
    {
        $this->getJson('/api/v1/mentors')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/incoming')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/outgoing')->assertStatus(401);
    }

    /**
     * An active student can retrieve the available mentor listing.
     */
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

    /**
     * An active student can submit a pending request to a valid mentor.
     */
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

    /**
     * A mentor can view an incoming request and accept it.
     */
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

    /**
     * A mentor can reject a pending incoming request.
     */
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

    /**
     * Requests cannot be created for a closed mentorship program.
     */
    public function test_student_cannot_send_request_to_a_closed_program(): void
    {
        $this->program->update(['status' => 'closed']);
        Sanctum::actingAs($this->studentUser);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('mentorship_requests', 0);
    }

    /**
     * Requests cannot be created after a mentorship program has expired.
     */
    public function test_student_cannot_send_request_to_an_expired_program(): void
    {
        $this->program->update(['end_date' => now()->subDay()->toDateString()]);
        Sanctum::actingAs($this->studentUser);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('mentorship_requests', 0);
    }

    /**
     * The same student cannot submit the same mentor-program request twice.
     */
    public function test_student_cannot_send_duplicate_request_for_same_program_and_mentor(): void
    {
        Sanctum::actingAs($this->studentUser);

        $payload = [
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
        ];

        $this->postJson('/api/v1/mentorship-requests', $payload)->assertStatus(201);
        $this->postJson('/api/v1/mentorship-requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mentor_id']);

        $this->assertDatabaseCount('mentorship_requests', 1);
    }

    /**
     * A student can request only a mentor from the same faculty.
     */
    public function test_student_cannot_request_a_mentor_from_another_faculty(): void
    {
        $otherFaculty = Faculty::factory()->create();
        $otherMajor = Major::factory()->create(['faculty_id' => $otherFaculty->id]);
        $otherMentor = User::factory()->create(['is_active' => true]);
        $otherMentor->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id' => $otherMentor->id,
            'major_id' => $otherMajor->id,
            'status' => 'active',
            'is_open_to_mentor' => true,
        ]);

        Sanctum::actingAs($this->studentUser);

        $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $otherMentor->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('mentorship_requests', 0);
    }

    /**
     * A student cannot select themselves as the requested mentor.
     */
    public function test_student_cannot_send_a_request_to_themselves(): void
    {
        Sanctum::actingAs($this->studentUser);

        $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id' => $this->studentUser->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mentor_id']);

        $this->assertDatabaseCount('mentorship_requests', 0);
    }

    /**
     * Only the mentor assigned to a request can accept that request.
     */
    public function test_only_the_mentor_can_accept_a_mentorship_request(): void
    {
        $request = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $this->studentUser->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->studentUser);

        $this->postJson("/api/v1/mentorship-requests/{$request->id}/accept")
            ->assertForbidden();

        $this->assertEquals('pending', $request->refresh()->status->value);
    }

    /**
     * A mentor cannot accept another request after reaching program capacity.
     */
    public function test_mentor_cannot_accept_a_request_after_reaching_capacity(): void
    {
        $this->program->update(['mentor_per_mentees_max' => 1]);
        $acceptedMentee = User::factory()->create(['is_active' => true]);
        $pendingMentee = User::factory()->create(['is_active' => true]);

        MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $acceptedMentee->id,
            'status' => 'accepted',
        ]);
        $pendingRequest = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id' => $this->mentorUser->id,
            'mentee_id' => $pendingMentee->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser);

        $this->postJson("/api/v1/mentorship-requests/{$pendingRequest->id}/accept")
            ->assertStatus(500);

        $this->assertEquals('pending', $pendingRequest->refresh()->status->value);
    }
}
