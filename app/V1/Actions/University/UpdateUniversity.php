<?php

namespace App\V1\Actions\University;

use App\Models\University;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for updating an existing university record.
 */
class UpdateUniversity
{
    /**
     * Handle the university update logic.
     *
     * @param  University $university
     * @param  array      $data
     * @return University
     * @throws Throwable
     */
    public function handle(University $university, array $data): University
    {
        try {
            $data['updated_by'] = auth()->id();
            $university->update($data);
            return $university->fresh();
        } catch (Throwable $exception) {
            Log::error('UpdateUniversity failed', [
                'university_id' => $university->id,
                'payload' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }
}
