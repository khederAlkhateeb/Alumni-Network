<?php

namespace App\V1\Actions\Reports;

use App\Enums\MentorshipRequestStatus;
use App\Models\MentorshipProgram;
use App\Models\MentorshipRequest;
use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetMentorshipStatsReportAction
{
    private const CACHE_TTL = 1800;

    /**
     * Handle the request for the mentorship stats report.
     *
     * @param University $university
     * @return array
     */
    public function handle(University $university): array
    {
        try {
            return Cache::remember(
                "university_{$university->id}_report_mentorship_stats",
                self::CACHE_TTL,
                function () use ($university) {
                    $programs = MentorshipProgram::query()
                        ->where('university_id', $university->id)
                        ->withCount([
                            'requests as accepted_requests_count' => fn ($query) => $query
                                ->where('status', MentorshipRequestStatus::ACCEPTED),
                        ])
                        ->get();

                    $requestsBuilder = MentorshipRequest::query()->forUniversity($university->id);

                    return [
                        'total_programs' => $programs->count(),
                        'programs_by_status' => $programs->groupBy('status')
                            ->map(fn ($group) => $group->count())
                            ->all(),
                        'total_requests' => (clone $requestsBuilder)->count(),
                        'requests_by_status' => $requestsBuilder->countByStatuses(),
                        'programs' => $programs->map(fn (MentorshipProgram $program) => [
                            'program_id' => $program->id,
                            'title' => $program->title,
                            'status' => $program->status,
                            'mentor_per_mentees_max' => $program->mentor_per_mentees_max,
                            'accepted_requests' => $program->accepted_requests_count,
                        ])->all(),
                    ];
                }
            );
        } catch (Throwable $exception) {
            Log::error('GetMentorshipStatsReport failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}