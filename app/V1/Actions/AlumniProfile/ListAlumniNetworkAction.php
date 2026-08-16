<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListAlumniNetworkAction
{
    /**
     * @param array{
     *     university_id?: int|null,
     *     major_id?: int|null,
     *     graduation_year?: int|null,
     *     skill_id?: int|array|null,
     *     is_open_to_mentor?: bool|string|null,
     *     per_page?: int|null
     * } $filters
     */
    public function handle(array $filters, ?int $per_page = null): LengthAwarePaginator
{
    $per_page = $filters['per_page']
        ?? $per_page
        ?? config('app.pagination.per_page');

    return AlumniProfile::query()
         ->with([
        'user',
        'skills',
        // 'workExperiences',
        // 'photo',
        'major.faculty.university',
    ])
        ->filters($filters)
        ->latest('created_at')
        ->paginate((int)$per_page);
}
}
