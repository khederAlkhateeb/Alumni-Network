<?php

namespace App\V1\Actions\Reports;

use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetJobsActivityReportAction
{
    private const CACHE_TTL = 1800;

    /**
     * Handle the request for the jobs activity report.
     *
     * @param University $university
     * @return array
     */
    public function handle(University $university): array
    {
        try {
            return Cache::remember(
                "university_{$university->id}_report_jobs_activity",
                self::CACHE_TTL,
                function () use ($university) {
                    $jobs = JobListing::query()
                        ->forUniversity($university->id)
                        ->withCount('applications')
                        ->get();

                    return [
                        'total_jobs' => $jobs->count(),
                        'jobs_by_status' => $jobs->groupBy('status')
                            ->map(fn ($group) => $group->count())
                            ->all(),
                        'total_applications' => $jobs->sum('applications_count'),
                        'applications_by_status' => JobApplication::query()->countByStatusesForUniversity($university),
                        'jobs' => $jobs->map(fn (JobListing $job) => [
                            'job_id' => $job->id,
                            'title' => $job->title,
                            'company' => $job->company,
                            'status' => $job->status,
                            'applications' => $job->applications_count,
                        ])->all(),
                    ];
                }
            );
        } catch (Throwable $exception) {
            Log::error('GetJobsActivityReport failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}