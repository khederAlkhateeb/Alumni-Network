<?php

namespace App\V1\Actions\University;

use App\Models\University;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for retrieving a single university.
 */
class GetUniversity
{
    /**
     * Handle retrieving the university.
     *
     * @param  University $university
     * @return University
     * @throws Throwable
     */
    public function handle(University $university): University
    {
        try {
            return $university;
        } catch (Throwable $exception) {
            Log::error('GetUniversity failed', [
                'university_id' => $university->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }
}
