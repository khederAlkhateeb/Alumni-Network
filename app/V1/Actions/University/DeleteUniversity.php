<?php

namespace App\V1\Actions\University;

use App\Models\University;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for soft-deleting a university record.
 */
class DeleteUniversity
{
    /**
     * Delete (soft-delete) the given university.
     *
     * @param  University $university
     * @return bool|null
     * @throws Throwable
     */
    public function handle(University $university): ?bool
    {
        try {
            return $university->delete();
        } catch (Throwable $exception) {
            Log::error('DeleteUniversity failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }
}
