<?php
namespace App\V1\Actions\GraduationRequest;

use App\Models\GraduationRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetGraduationRequestsAction
{
    /**
     * Fetch paginated graduation requests using custom Query Builder.
     *
     * @param array{status?: string, major_id?: int, search?: string} $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function handle(array $filters = [], ? int $perPage = 15): LengthAwarePaginator
    {

      $perPage = $filters['per_page']
        ?? $perPage
        ?? config('app.pagination.per_page');
        return GraduationRequest::query()
            ->with([
                'studentProfile.user',
                'studentProfile.major',
                'reviewer',
            ])
            ->filter($filters)
            ->latest()
            ->paginate($perPage);
    }
}
