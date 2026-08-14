<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\University;
use App\V1\Actions\University\GetUniversityStatsAction;
use Illuminate\Http\Request;

/**
 * Handle the incoming request.
 * this class get all the status that the university admin or super admin
 *  need to know about the university
 */
class UniversityStatsController extends Controller
{
    /**
     * Handle the incoming request to fetch university KPI statistics.
     */
    public function __invoke(University $university, GetUniversityStatsAction $action)
    {
        $this->authorize('viewStats', $university);
        $status = $action->handle($university);

        return $this->successResponse(
            data: $status,
            message: "University stats retrived successfully"
        );
    }
}
