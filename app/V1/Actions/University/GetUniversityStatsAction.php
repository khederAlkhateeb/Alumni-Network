<?php

namespace App\V1\Actions\University;

use App\Enums\MentorshipRequestStatus;
use App\Models\AlumniProfile;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JobApplication;
use App\Models\JobListing;
use App\Models\MentorshipRequest;
use App\Models\Post;
use App\Models\StudentProfile;
use App\Models\University;
use App\V1\Actions\Reports\GetAlumniOverviewReportAction;
use App\V1\Actions\Reports\GetEmploymentRateReportAction;
use Illuminate\Support\Facades\Cache;



class GetUniversityStatsAction
{

    public function __construct(
        private GetAlumniOverviewReportAction $alumniOverview,
        private GetEmploymentRateReportAction $employmentRate,
    ) {
    }
    /**
     * Execute the business logic to gather KPI stats.
     */
    public function handle(University $university)
    {
        $cacheKey = "university_stats_{$university->id}";
        $cacheTtl = now()->addMinutes(30);
        $uniId = $university->id;

        return Cache::remember($cacheKey, $cacheTtl, function () use ($uniId, $university) {
            $mentorshipRequestCount = MentorshipRequest::forUniversity($uniId)->countByStatuses();
            $alumniOverviewData = $this->alumniOverview->handle($university);
            $employmentRateData = $this->employmentRate->handle($university);

            return [
                'university' => [
                    'id' => $uniId,
                    'name' => $university->name,
                ],
                'overview' => [
                    'total_alumni' => $alumniOverviewData['total_alumni'],
                    'total_students' => StudentProfile::query()->sameUniversityAs($uniId)->count(),
                    'pending_approvals' => $university->pending_approvals,
                ],
                'employment_kpis' => [
                    'employment_rate' => $employmentRateData['employment_rate'],
                    'open_job_postings' => JobListing::query()->forUniversity($university->id)->open()->count('id'),
                    'total_applications_submitted' => JobApplication::countByStatusesForUniversity($university),
                ],
                'mentorship_kpis' => [
                    'active_mentors' => AlumniProfile::sameUniversityAs($uniId)->where('is_open_to_mentor', true)->count(),
                    'ongoing_mentorship_sessions' => $mentorshipRequestCount[MentorshipRequestStatus::ACCEPTED->value],
                    'pending_mentorship_requests' => $mentorshipRequestCount[MentorshipRequestStatus::PENDING->value],
                ],
                'events_kpis' => [
                    'upcoming_events' => Event::query()->forUniversity($uniId)->where('start_date', '>', now())->count(),
                    'total_registrations_this_month' => EventRegistration::query()->forUniversity($uniId)
                        ->whereMonth('created_at', now()->month)
                        ->count(),
                ],
                'community_kpis' => [
                    'total_posts' => Post::UniversityAnnouncements($uniId)->count('id'),
                ],
            ];
        });
    }
}
