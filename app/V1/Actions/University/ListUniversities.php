<?php

namespace App\V1\Actions\University;

use App\Models\Scopes\UniversityScope;
use App\Models\University;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for retrieving paginated university lists.
 */
class ListUniversities
{
    /**
     * Get a paginated list of all universities, bypassing global scopes (public route).
     * Supports optional filtering by name and country.
     *
     * @param  int   $per_page
     * @param  array $filters  Supported keys: 'name', 'country'
     * @return LengthAwarePaginator
     * @throws Throwable
     */
    public function handle(int $per_page = 15, array $filters = []): LengthAwarePaginator
    {
        try {
            return University::withoutGlobalScope(UniversityScope::class)
                ->when(!empty($filters['name']), function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . trim($filters['name']) . '%');
                })
                ->when(!empty($filters['country']), function ($query) use ($filters) {
                    $query->where('country', 'like', '%' . trim($filters['country']) . '%');
                })
                ->paginate($per_page ?? config('app.pagination.per_page'));
        } catch (Throwable $exception) {
            Log::error('ListUniversities failed', [
                'per_page' => $per_page,
                'filters' => $filters,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }
}
