<?php

use App\Http\Controllers\Api\V1\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| University Reports routes
|--------------------------------------------------------------------------
|
| All endpoints are scoped to a specific university, require a valid
| Sanctum access token, and are restricted to the "uni_admin" role
| (a super_admin also passes via UniversityPolicy::viewReports()).
| The "university" binding uses scopeBindings() to stay consistent
| with events.php.
|
*/

Route::middleware('auth:sanctum')
    ->prefix('universities/{university}/reports')
    ->scopeBindings()
    ->group(function () {

        /**
         * Alumni distribution by major and graduation year.
         * @see ReportController::alumniOverview()
         */
        Route::get('/alumni-overview', [ReportController::class, 'alumniOverview'])
            ->name('api.reports.alumni-overview');

        /**
         * Employment rate and top employers among alumni.
         * @see ReportController::employmentRate()
         */
        Route::get('/employment-rate', [ReportController::class, 'employmentRate'])
            ->name('api.reports.employment-rate');

        /**
         * Mentorship programs and requests statistics.
         * @see ReportController::mentorshipStats()
         */
        Route::get('/mentorship-stats', [ReportController::class, 'mentorshipStats'])
            ->name('api.reports.mentorship-stats');

        /**
         * Events registration and attendance engagement.
         * @see ReportController::eventsEngagement()
         */
        Route::get('/events-engagement', [ReportController::class, 'eventsEngagement'])
            ->name('api.reports.events-engagement');

        /**
         * Job listings and applications activity.
         * @see ReportController::jobsActivity()
         */
        Route::get('/jobs-activity', [ReportController::class, 'jobsActivity'])
            ->name('api.reports.jobs-activity');
    });