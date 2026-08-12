<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\V1\Actions\Reports\GetAlumniOverviewReportAction;
use App\V1\Actions\Reports\GetEmploymentRateReportAction;
use App\V1\Actions\Reports\GetEventsEngagementReportAction;
use App\V1\Actions\Reports\GetJobsActivityReportAction;
use App\V1\Actions\Reports\GetMentorshipStatsReportAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(
        private readonly GetAlumniOverviewReportAction $alumniOverviewReport,
        private readonly GetEmploymentRateReportAction $employmentRateReport,
        private readonly GetMentorshipStatsReportAction $mentorshipStatsReport,
        private readonly GetEventsEngagementReportAction $eventsEngagementReport,
        private readonly GetJobsActivityReportAction $jobsActivityReport,
    ) {
    }

    /**
     * Display the alumni overview report.
     *
     * @param University $university
     * @return JsonResponse
     */
    public function alumniOverview(University $university): JsonResponse
    {
        Gate::authorize('viewReports', $university);

        return $this->successResponse(
            data: $this->alumniOverviewReport->handle($university),
            message: __('Alumni overview report retrieved successfully'),
        );
    }

    /**
     * Display the employment rate report.
     *
     * @param University $university
     * @return JsonResponse
     */
    public function employmentRate(University $university): JsonResponse
    {
        Gate::authorize('viewReports', $university);

        return $this->successResponse(
            data: $this->employmentRateReport->handle($university),
            message: __('Employment rate report retrieved successfully'),
        );
    }

    /**
     * Display the mentorship stats report.
     *
     * @param University $university
     * @return JsonResponse
     */
    public function mentorshipStats(University $university): JsonResponse
    {
        Gate::authorize('viewReports', $university);

        return $this->successResponse(
            data: $this->mentorshipStatsReport->handle($university),
            message: __('Mentorship stats report retrieved successfully'),
        );
    }

    /**
     * Display the events engagement report.
     *
     * @param University $university
     * @return JsonResponse
     */
    public function eventsEngagement(University $university): JsonResponse
    {
        Gate::authorize('viewReports', $university);

        return $this->successResponse(
            data: $this->eventsEngagementReport->handle($university),
            message: __('Events engagement report retrieved successfully'),
        );
    }

    /**
     * Display the jobs activity report.
     *
     * @param University $university
     * @return JsonResponse
     */
    public function jobsActivity(University $university): JsonResponse
    {
        Gate::authorize('viewReports', $university);

        return $this->successResponse(
            data: $this->jobsActivityReport->handle($university),
            message: __('Jobs activity report retrieved successfully'),
        );
    }
}
