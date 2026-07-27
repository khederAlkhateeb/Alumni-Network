<?php

namespace App\V1\Actions\Faculty;
use App\Models\Faculty;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for deleting a faculty record.
 */
class DeleteFaculty
{
    /**
     * @param  Faculty $faculty
     */
    public function handle(Faculty $faculty): void
    {
        try {
            $faculty->delete();
        } catch (Throwable $exception) {
            Log::error('DeleteFaculty failed', ['faculty_id' => $faculty->id, 'error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw $exception;
        }
    }
}
