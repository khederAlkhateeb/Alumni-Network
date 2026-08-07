<?php

namespace App\V1\Actions\Reports;

use App\Models\AlumniProfile;
use App\Models\University;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GetAlumniOverviewReportAction
{
    private const CACHE_TTL = 1800;

    /**
     * Handle the request for the alumni overview report.
     *
     * @param University $university
     * @return array
     */
    public function handle(University $university): array
    {
        try {
            return Cache::remember(
                "university_{$university->id}_report_alumni_overview",
                self::CACHE_TTL,
                fn () => [
                    'total_alumni' => AlumniProfile::query()->forUniversity($university->id)->count(),
                    'by_major' => AlumniProfile::query()->forUniversity($university->id)->byMajorSummary(),
                    'by_graduation_year' => AlumniProfile::query()->forUniversity($university->id)->byGraduationYearSummary(),
                ]
            );
        } catch (Throwable $exception) {
            Log::error('GetAlumniOverviewReport failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}