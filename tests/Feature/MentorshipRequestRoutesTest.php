<?php

use App\Models\AlumniProfile;
use App\Models\Conversation;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * @file MentorshipFeatureTest.php
 *
 * Mentorship Feature Test Suite.
 * Covers security guards, mentor discovery, request submissions, validation rules,
 * workflow state transitions (acceptance/rejection), and capacity constraints.
 */

uses(RefreshDatabase::class);

/**
 * IDE Type Hinting for Pest Dynamic Properties
 *
 * @property User $studentUser
 * @property User $mentorUser
 * @property MentorshipProgram $program
 */

/*
|--------------------------------------------------------------------------
| Environment & Context Setup
|--------------------------------------------------------------------------
*/

/**
 * Setup test environment before each test execution.
 *
 * Seeds permissions and roles for both 'sanctum' and 'api' guards, assigns permissions to
 * 'student' and 'alumni' roles, and initializes test fixtures (faculty, major, student user,
 * mentor user, and an active mentorship program).
 *
 * @return void
 */
beforeEach(function () {
    $guards = ['sanctum', 'api'];

    foreach ($guards as $guard) {
        $viewMentors        = Permission::findOrCreate('view-available-mentors', $guard);
        $sendRequest        = Permission::findOrCreate('send-mentorship-request', $guard);
        $acceptRequest      = Permission::findOrCreate('accept-mentorship-request', $guard);
        $rejectRequest      = Permission::findOrCreate('reject-mentorship-request', $guard);
        $completeMentorship = Permission::findOrCreate('complete-mentorship', $guard);

        $studentRole = Role::findOrCreate('student', $guard);
        $studentRole->givePermissionTo([
            $viewMentors,
            $sendRequest,
            $completeMentorship,
        ]);

        $alumniRole = Role::findOrCreate('alumni', $guard);
        $alumniRole->givePermissionTo([
            $acceptRequest,
            $rejectRequest,
            $completeMentorship,
        ]);
    }

    $faculty = Faculty::factory()->create();
    $major   = Major::factory()->create(['faculty_id' => $faculty->id]);

    // 1. Definition of Student User
    /** @var User $studentUser */
    $studentUser = User::factory()->create(['is_active' => true]);
    $studentUser->assignRole('student');
    StudentProfile::factory()->create([
        'user_id'  => $studentUser->id,
        'major_id' => $major->id,
        'status'   => 'active',
    ]);
    test()->studentUser = $studentUser;

    // 2. Definition of Mentor User
    /** @var User $mentorUser */
    $mentorUser = User::factory()->create(['is_active' => true]);
    $mentorUser->assignRole('alumni');
    AlumniProfile::factory()->create([
        'user_id'           => $mentorUser->id,
        'major_id'          => $major->id,
        'status'            => 'active',
        'is_open_to_mentor' => true,
    ]);
    test()->mentorUser = $mentorUser;

    // 3. Definition of Mentorship Program
    /** @var MentorshipProgram $program */
    $program = MentorshipProgram::factory()->create([
        'university_id' => $studentUser->studentProfile->major->faculty->university_id,
        'title'         => 'Tech Mentorship 2026',
        'start_date'    => now()->subDay()->toDateString(),
        'end_date'      => now()->addMonth()->toDateString(),
        'status'        => 'active',
    ]);
    test()->program = $program;
});

/*
|--------------------------------------------------------------------------
| Mentorship Feature Test Suite
|--------------------------------------------------------------------------
*/

/**
 * Authentication and endpoint security tests.
 */
describe('Mentorship API Endpoint Security & Authorization Guards', function () {

    /**
     * Test that unauthenticated guest requests are rejected across mentorship endpoints.
     *
     * @test
     * @expectedStatus 401 Unauthorized
     */
    it('prevents unauthenticated guests from accessing any mentorship services', function () {
        $this->getJson('/api/v1/mentors')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/incoming')->assertStatus(401);
        $this->getJson('/api/v1/mentorship-requests/outgoing')->assertStatus(401);
    });

});

/**
 * Mentor discovery and listing query tests.
 */
describe('Available Mentors Discovery & Listing', function () {

    /**
     * Test fetching a structured list of available alumni mentors for an authenticated student.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('returns a structured list of available alumni mentors for authenticated students', function () {
        MentorshipRequest::create([
            'program_id'    => $this->program->id,
            'mentor_id'     => $this->mentorUser->id,
            'mentee_id'     => $this->studentUser->id,
            'status'        => 'pending',
            'intro_message' => 'Setup message',
        ]);

        Sanctum::actingAs($this->studentUser, ['*']);

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
                    ],
                ],
            ]);
    });

});

/**
 * Request creation validation rules and domain constraints tests.
 */
describe('Mentorship Request Submission & Validation Rules', function () {

    /**
     * Test successful creation of a mentorship request by an eligible student.
     *
     * @test
     * @expectedStatus 201 Created
     */
    it('allows an eligible student to submit a mentorship request with valid payload', function () {
        Sanctum::actingAs($this->studentUser, ['*']);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id'    => $this->program->id,
            'mentor_id'     => $this->mentorUser->id,
            'intro_message' => 'Hello, I want to learn web development.',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request submitted successfully.');

        $this->assertDatabaseHas('mentorship_requests', [
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $this->studentUser->id,
            'status'     => 'pending',
        ]);
    });

    /**
     * Test that requests targeting a closed mentorship program are rejected.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('rejects mentorship requests targeting a closed program', function () {
        $this->program->update(['status' => 'closed']);
        Sanctum::actingAs($this->studentUser, ['*']);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('mentorship_requests', 0);
    });

    /**
     * Test that requests targeting an expired program are rejected.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('rejects mentorship requests targeting an expired program date', function () {
        $this->program->update(['end_date' => now()->subDay()->toDateString()]);
        Sanctum::actingAs($this->studentUser, ['*']);

        $response = $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('mentorship_requests', 0);
    });

    /**
     * Test prevention of duplicate pending requests from the same student to the same mentor.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('prevents duplicate pending requests to the same mentor within the same program', function () {
        Sanctum::actingAs($this->studentUser, ['*']);

        $payload = [
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
        ];

        $this->postJson('/api/v1/mentorship-requests', $payload)->assertStatus(201);
        $this->postJson('/api/v1/mentorship-requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mentor_id']);

        $this->assertDatabaseCount('mentorship_requests', 1);
    });

    /**
     * Test enforcement of academic boundaries preventing cross-faculty mentorship requests.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('enforces academic boundary rules preventing cross-faculty mentor requests', function () {
        $otherFaculty = Faculty::factory()->create();
        $otherMajor   = Major::factory()->create(['faculty_id' => $otherFaculty->id]);

        /** @var User $otherMentor */
        $otherMentor  = User::factory()->create(['is_active' => true]);
        $otherMentor->assignRole('alumni');
        AlumniProfile::factory()->create([
            'user_id'           => $otherMentor->id,
            'major_id'          => $otherMajor->id,
            'status'            => 'active',
            'is_open_to_mentor' => true,
        ]);

        Sanctum::actingAs($this->studentUser, ['*']);

        $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id'  => $otherMentor->id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('mentorship_requests', 0);
    });

    /**
     * Test prevention of self-referral mentorship requests.
     *
     * @test
     * @expectedStatus 422 Unprocessable Entity
     */
    it('prevents users from sending self-referral mentorship requests', function () {
        Sanctum::actingAs($this->studentUser, ['*']);

        $this->postJson('/api/v1/mentorship-requests', [
            'program_id' => $this->program->id,
            'mentor_id'  => $this->studentUser->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['mentor_id']);

        $this->assertDatabaseCount('mentorship_requests', 0);
    });

});

/**
 * Mentor decision workflows (accept/reject) and program capacity limit tests.
 */
describe('Mentor Request Decision Workflow & Capacity Constraints', function () {

    /**
     * Test fetching incoming requests and accepting them as a mentor.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows designated alumni mentors to fetch incoming requests and accept them', function () {
        $req = MentorshipRequest::create([
            'program_id'    => $this->program->id,
            'mentor_id'     => $this->mentorUser->id,
            'mentee_id'     => $this->studentUser->id,
            'intro_message' => 'Intro message',
            'status'        => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $incomingResponse = $this->getJson('/api/v1/mentorship-requests/incoming');
        $incomingResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data');

        $acceptResponse = $this->postJson("/api/v1/mentorship-requests/{$req->id}/accept");
        $acceptResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request accepted successfully.');

        $this->assertEquals('accepted', $req->refresh()->status->value ?? $req->refresh()->status);
    });

    /**
     * Test rejecting an incoming request as a mentor.
     *
     * @test
     * @expectedStatus 200 OK
     */
    it('allows designated alumni mentors to reject incoming requests', function () {
        $req = MentorshipRequest::create([
            'program_id'    => $this->program->id,
            'mentor_id'     => $this->mentorUser->id,
            'mentee_id'     => $this->studentUser->id,
            'intro_message' => 'Intro message',
            'status'        => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $rejectResponse = $this->postJson("/api/v1/mentorship-requests/{$req->id}/reject");
        $rejectResponse->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Mentorship request rejected successfully.');

        $this->assertEquals('rejected', $req->refresh()->status->value ?? $req->refresh()->status);
    });

    /**
     * Test that non-mentor accounts (e.g., mentees) are forbidden from approving requests.
     *
     * @test
     * @expectedStatus 403 Forbidden
     */
    it('forbids non-mentor users (e.g. mentees) from approving mentorship requests', function () {
        $request = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $this->studentUser->id,
            'status'     => 'pending',
        ]);

        Sanctum::actingAs($this->studentUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$request->id}/accept")
            ->assertForbidden();

        $this->assertEquals('pending', $request->refresh()->status->value ?? $request->refresh()->status);
    });

    /**
     * Test blocking request acceptance when mentor maximum mentee capacity is reached.
     *
     * @test
     * @expectedStatus 500 Internal Server Error
     */
    it('blocks request acceptance when the mentor has reached maximum mentee capacity', function () {
        $this->program->update(['mentor_per_mentees_max' => 1]);

        /** @var User $acceptedMentee */
        $acceptedMentee = User::factory()->create(['is_active' => true]);

        /** @var User $pendingMentee */
        $pendingMentee  = User::factory()->create(['is_active' => true]);

        MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $acceptedMentee->id,
            'status'     => 'accepted',
        ]);
        $pendingRequest = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $pendingMentee->id,
            'status'     => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$pendingRequest->id}/accept")
            ->assertStatus(500);

        $this->assertEquals('pending', $pendingRequest->refresh()->status->value ?? $pendingRequest->refresh()->status);
    });

});
  describe(' Mentorship Request Regression Tests', function () {

    /**
     * A pending request can be accepted once, but a rejected request * be accepted again.
     */
    it('does not allow a rejected request to be accepted again', function () {
        $request = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $this->studentUser->id,
            'status'     => 'rejected',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$request->id}/accept")
            ->assertStatus(500);

        expect($request->fresh()->status->value ?? $request->fresh()->status)
            ->toBe('rejected');
    });

    /**
     * A completed request cannot be modified again.
     */
    it('does not allow a completed request to be modified again', function () {
        $request = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $this->studentUser->id,
            'status'     => 'complete',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$request->id}/reject")
            ->assertStatus(500);

        expect($request->fresh()->status->value ?? $request->fresh()->status)
            ->toBe('complete');
    });

    /**
     * A mentor cannot accept another request after reaching capacity.
     * This is the sequential regression test for the capacity rule.
     */
    it('keeps the pending request pending when mentor capacity is full', function () {
        $this->program->update(['mentor_per_mentees_max' => 1]);

        $acceptedMentee = User::factory()->create(['is_active' => true]);
        $pendingMentee  = User::factory()->create(['is_active' => true]);

        MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $acceptedMentee->id,
            'status'     => 'accepted',
        ]);

        $pendingRequest = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $pendingMentee->id,
            'status'     => 'pending',
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$pendingRequest->id}/accept")
            ->assertStatus(500);

        expect($pendingRequest->fresh()->status->value ?? $pendingRequest->fresh()->status)
            ->toBe('pending');

        $this->assertDatabaseCount('mentorship_requests', 2);
    });

    /**
     * The status-change event must be dispatched when a request is accepted.
     * Event::fake() intentionally isolates dispatch verification from the
     * listener integration test below.
     */
    it('dispatches the mentorship status event when a request is accepted', function () {
        $request = MentorshipRequest::create([
            'program_id' => $this->program->id,
            'mentor_id'  => $this->mentorUser->id,
            'mentee_id'  => $this->studentUser->id,
            'status'     => 'pending',
        ]);

        \Illuminate\Support\Facades\Event::fake([
            \App\Events\MentorshipRequestStatusUpdated::class,
        ]);

        Sanctum::actingAs($this->mentorUser, ['*']);

        $this->postJson("/api/v1/mentorship-requests/{$request->id}/accept")
            ->assertStatus(200);

        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\MentorshipRequestStatusUpdated::class,
            function ($event) use ($request) {
                return $event->mentorshipRequest->id === $request->id
                    && $event->mentorshipRequest->status->value === 'accepted'
                    && $event->previousStatus->value === 'pending';
            }
        );

});

});
it('creates a conversation when a mentorship request is accepted', function () {
    // Arrange
    $request = MentorshipRequest::create([
        'program_id' => $this->program->id,
        'mentor_id'  => $this->mentorUser->id,
        'mentee_id'  => $this->studentUser->id,
        'status'     => 'pending',
    ]);

    Sanctum::actingAs($this->mentorUser, ['*']);

    // Act
    $this->postJson("/api/v1/mentorship-requests/{$request->id}/accept")
        ->assertStatus(200);

    // Assert
$this->assertTrue(
    Conversation::where(function ($q) {
        $q->where('user_one_id', $this->mentorUser->id)
          ->where('user_two_id', $this->studentUser->id);
    })->orWhere(function ($q) {
        $q->where('user_one_id', $this->studentUser->id)
          ->where('user_two_id', $this->mentorUser->id);
    })->exists()
);

});

