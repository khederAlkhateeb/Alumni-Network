<?php

namespace Tests\Unit;

use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MentorshipCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mentor_has_not_reached_limit_when_accepted_count_is_below_capacity(): void
    {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 2,
        ]);

        $this->createRequest($program, $mentor, 'accepted');

        $this->assertFalse($mentor->hasReachedLimit($program->id));
    }

    public function test_mentor_has_reached_limit_at_exact_capacity(): void
    {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 2,
        ]);

        $this->createRequest($program, $mentor, 'accepted');
        $this->createRequest($program, $mentor, 'accepted');

        $this->assertTrue($mentor->hasReachedLimit($program->id));
    }

    public function test_mentor_has_reached_limit_when_accepted_count_exceeds_capacity(): void
    {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        $this->createRequest($program, $mentor, 'accepted');
        $this->createRequest($program, $mentor, 'accepted');

        $this->assertTrue($mentor->hasReachedLimit($program->id));
    }

    public function test_only_accepted_requests_count_towards_capacity(): void
    {
        $mentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        $this->createRequest($program, $mentor, 'pending');
        $this->createRequest($program, $mentor, 'rejected');
        $this->createRequest($program, $mentor, 'complete');

        $this->assertFalse($mentor->hasReachedLimit($program->id));
    }

    public function test_capacity_is_scoped_to_the_same_mentor_and_program(): void
    {
        $mentor = User::factory()->create();
        $otherMentor = User::factory()->create();
        $program = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);
        $otherProgram = MentorshipProgram::factory()->create([
            'mentor_per_mentees_max' => 1,
        ]);

        $this->createRequest($program, $otherMentor, 'accepted');
        $this->createRequest($otherProgram, $mentor, 'accepted');

        $this->assertFalse($mentor->hasReachedLimit($program->id));
    }

    public function test_missing_program_does_not_mark_mentor_as_full(): void
    {
        $mentor = User::factory()->create();

        $this->assertFalse($mentor->hasReachedLimit(PHP_INT_MAX));
    }

    private function createRequest(MentorshipProgram $program, User $mentor, string $status): MentorshipRequest
    {
        $mentee = User::factory()->create();

        return MentorshipRequest::create([
            'program_id' => $program->id,
            'mentor_id' => $mentor->id,
            'mentee_id' => $mentee->id,
            'status' => $status,
        ]);
    }
}