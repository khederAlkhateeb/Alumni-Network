<?php

namespace App\V1\Actions\Faculty;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for retrieving a faculty listing.
 */
class ListFaculties
{
    /**
     * Return all faculties.
     *
     * @return Collection
     */
    public function handle(array $filters = []): Collection
    {
        try {
            return Faculty::with('university')
                ->when(!empty($filters['name']), function ($query) use ($filters) {
                    $query->where('name', 'like', '%' . trim($filters['name']) . '%');
                })
                ->get();
        } catch (Throwable $exception) {
            Log::error('ListFaculties failed', ['error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw $exception;
        }
    }
}
