<?php

use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Create a mentorship request for a specific mentor, program, and status.
 *
 * A separate mentee is created for each request to satisfy the database
 * relationship and unique constraint requirements.
 *
 * @param MentorshipProgram $program
 * @param User $mentor
 * @param string $status
 * @return MentorshipRequest
 */
function createMentorshipRequest(MentorshipProgram $program, User $mentor, string $status): MentorshipRequest
{
    $mentee = User::factory()->create();

    return MentorshipRequest::create([
        'program_id' => $program->id,
        'mentor_id' => $mentor->id,
        'mentee_id' => $mentee->id,
        'status' => $status,
    ]);
}

/**
 * Feature/Unit tests for the mentor capacity calculation.
 *
 * The capacity is based only on accepted mentorship requests and is scoped
 * to the selected mentor and mentorship program.
 */
describe('Mentor Capacity Calculation', function () {

    /**
     * A mentor remains available when accepted requests are below capacity.
     */
    it('remains available when accepted requests are below capacity', function () {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 2,
        ]);

        createMentorshipRequest($program, $mentor, 'accepted');

        expect($mentor->hasReachedLimit($program->id))->toBeFalse();
    });

    /**
     * A mentor is full when accepted requests equal the configured capacity.
     */
    it('has reached limit at exact capacity', function () {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 2,
        ]);

        createMentorshipRequest($program, $mentor, 'accepted');
        createMentorshipRequest($program, $mentor, 'accepted');

        expect($mentor->hasReachedLimit($program->id))->toBeTrue();
    });

    /**
     * A mentor remains full when accepted requests exceed capacity.
     */
    it('has reached limit when accepted count exceeds capacity', function () {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        createMentorshipRequest($program, $mentor, 'accepted');
        createMentorshipRequest($program, $mentor, 'accepted');

        expect($mentor->hasReachedLimit($program->id))->toBeTrue();
    });

    /**
     * Pending, rejected, and completed requests do not consume capacity.
     */
    it('only counts accepted requests towards capacity', function () {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        createMentorshipRequest($program, $mentor, 'pending');
        createMentorshipRequest($program, $mentor, 'rejected');
        createMentorshipRequest($program, $mentor, 'complete');

        expect($mentor->hasReachedLimit($program->id))->toBeFalse();
    });

    /**
     * Requests belonging to another mentor or program do not affect capacity.
     */
    it('is scoped to the same mentor and program', function () {
        $mentor = User::factory()->create();
        $otherMentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);
        $otherProgram = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        createMentorshipRequest($program, $otherMentor, 'accepted');
        createMentorshipRequest($otherProgram, $mentor, 'accepted');

        expect($mentor->hasReachedLimit($program->id))->toBeFalse();
    });

    /**
     * A missing program does not make the mentor appear to be at capacity.
     */
    it('does not mark mentor as full on missing program', function () {
        $mentor = User::factory()->create();

        expect($mentor->hasReachedLimit(PHP_INT_MAX))->toBeFalse();
    });

});
