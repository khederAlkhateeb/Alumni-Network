<?php

namespace App\V1\Actions\Reports;

use App\Models\AlumniProfile;
use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetEmploymentRateReportAction
{
    private const CACHE_TTL = 1800;
    private const TOP_COMPANIES_LIMIT = 5;

    /**
     * Handle the request for the employment rate report.
     *
     * @param University $university
     * @return array
     */
    public function handle(University $university): array
    {
        try {
            return Cache::remember(
                "university_{$university->id}_report_employment_rate",
                self::CACHE_TTL,
                function () use ($university) {
                    $baseQuery = AlumniProfile::query()->forUniversity($university->id);

                    $totalAlumni = (clone $baseQuery)->count();
                    $employedAlumni = (clone $baseQuery)->employed()->count();

                    return [
                        'total_alumni' => $totalAlumni,
                        'employed_alumni' => $employedAlumni,
                        'employment_rate' => $totalAlumni > 0
                            ? round(($employedAlumni / $totalAlumni) * 100, 1)
                            : 0.0,
                        'top_companies' => $baseQuery->topCompaniesSummary(),
                    ];
                }
            );
        } catch (Throwable $exception) {
            Log::error('GetEmploymentRateReport failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}