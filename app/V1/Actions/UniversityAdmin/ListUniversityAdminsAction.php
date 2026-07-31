<?php

namespace App\V1\Actions\UniversityAdmin;

use App\Models\UniversityAdmin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListUniversityAdminsAction
{
    /**
     * View university admins list
     *
     * @param integer $perPage
     * @return LengthAwarePaginator
     */
    public function handle(int $perPage = 15): LengthAwarePaginator
    {
        return UniversityAdmin::with(['user', 'university'])
            ->latest()
            ->paginate($perPage);
    }
}