<?php

namespace App\V1\Actions\Major;

use App\Models\Faculty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handles fetching all majors for a specific faculty with pagination.
 */
class ListMajorAction
{
    /**
     * Retrieve paginated majors for the given faculty.
     *
     * @param Faculty $faculty
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function handle(Faculty $faculty, int $perPage = 15): LengthAwarePaginator
    {
        return $faculty->majors()
            ->latest('id')
            ->paginate($perPage);
    }
}