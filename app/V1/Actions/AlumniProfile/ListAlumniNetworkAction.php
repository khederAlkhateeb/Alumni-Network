<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Fetches a paginated list of active alumni with dynamic filters available to all users.
 */
class ListAlumniNetworkAction
{
    /**
     * Execute the query to list alumni using custom builder scopes.
     *
     * @param array{
     *     university_id?: int|null,
     *     major_id?: int|null,
     *     graduation_year?: int|null,
     *     skill_id?: int|array|null,
     *     is_open_to_mentor?: bool|string|null,
     *     per_page?: int|null
     * } $filters
     * @return LengthAwarePaginator
     */
    public function execute(array $filters): LengthAwarePaginator
    {
        return AlumniProfile::query()
            ->with([
                'user',
                // 'major.faculty.university',
                'skills',
                'workExperiences',
                'photo',
            ])
            ->active()
            ->when(
                filled($filters['university_id'] ?? null),
                fn ($query) => $query->sameUniversityAs((int) $filters['university_id'])
            )
            ->when(
                filled($filters['major_id'] ?? null),
                fn ($query) => $query->where('major_id', $filters['major_id'])
            )
            ->when(
                filled($filters['graduation_year'] ?? null),
                fn ($query) => $query->graduatedIn((int) $filters['graduation_year'])
            )
            ->when(
                filled($filters['skill_id'] ?? null),
                fn ($query) => $query->withSkills((array) $filters['skill_id'])
            )
            ->when(
                filter_var($filters['is_open_to_mentor'] ?? false, FILTER_VALIDATE_BOOLEAN),
                fn ($query) => $query->openToMentor()
            )
            ->latest('created_at')
            ->paginate(perPage: $filters['per_page'] ?? 20);
    }
}
