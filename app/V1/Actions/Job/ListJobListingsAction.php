<?php

namespace App\V1\Actions\Job;

use App\Models\JobListing;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lists job listings with optional filters.
 *
 * The action supports filtering by university, type, status, and posting user,
 * then eager-loads related data for the API response.
 */
class ListJobListingsAction
{
    /**
     * Retrieve job listings based on the supplied filter set.
     *
     * @param array $filters Optional query filters.
     *
     * @return Collection
     */
    public function handle(array $filters = []): Collection
    {
        return JobListing::query()
            ->when(! empty($filters['university_id']), fn ($query) => $query->where('university_id', $filters['university_id']))
            ->when(! empty($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(! empty($filters['posted_by_user_id']), fn ($query) => $query->where('posted_by_user_id', $filters['posted_by_user_id']))
            ->with(['university', 'postedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
