<?php

namespace App\V1\Actions\Faculty;

use App\Models\Faculty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for updating an existing faculty record.
 */
class UpdateFaculty
{
    /**
     * @param  Faculty $faculty
     * @param  array<string, mixed> $data
     * @return Faculty
     */
    public function handle(Faculty $faculty, array $data): Faculty
    {
        try {
            $faculty->update([
                'name' => $data['name'] ?? $faculty->name,
                'university_id' => $data['university_id'] ?? $faculty->university_id,
            ]);
            return $faculty->fresh();
        } catch (Throwable $exception) {
            Log::error('UpdateFaculty failed', ['faculty_id' => $faculty->id, 'payload' => $data, 'error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw $exception;
        }
    }
}
