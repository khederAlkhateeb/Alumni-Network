<?php

use App\Contracts\UniversityContext;
use App\Models\Event;
use App\Models\JobListing;
use App\Models\MentorshipProgram;
use App\Models\University;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->currentUniversity = University::factory()->create();
    $this->otherUniversity = University::factory()->create();

    app()->instance(
        UniversityContext::class,
        new class ($this->currentUniversity->id) implements UniversityContext {
        public function __construct(private int $universityId)
        {}

        public function getUniversityId(): ?int
        {
            return $this->universityId;
        }

        public function isSuperAdmin(): bool
        {
            return false;
        }

        public function isGuest(): bool
        {
            return false;
        }
        }
    );
});

it('applies the university scope to events', function () {
    Event::factory()->count(2)->create(['university_id' => $this->currentUniversity->id]);
    Event::factory()->create(['university_id' => $this->otherUniversity->id]);

    expect(Event::query()->count())->toBe(2)
        ->and(Event::query()->pluck('university_id')->unique()->all())->toBe([$this->currentUniversity->id]);
});

it('applies the university scope to mentorship programs', function () {
    MentorshipProgram::factory()->count(2)->create(['university_id' => $this->currentUniversity->id]);
    MentorshipProgram::factory()->create(['university_id' => $this->otherUniversity->id]);

    expect(MentorshipProgram::query()->count())->toBe(2)
        ->and(MentorshipProgram::query()->pluck('university_id')->unique()->all())->toBe([$this->currentUniversity->id]);
});

it('applies the university scope to job listings', function () {
    JobListing::factory()->count(2)->create(['university_id' => $this->currentUniversity->id]);
    JobListing::factory()->create(['university_id' => $this->otherUniversity->id]);

    expect(JobListing::query()->count())->toBe(2)
        ->and(JobListing::query()->pluck('university_id')->unique()->all())->toBe([$this->currentUniversity->id]);
});
