<?php

namespace App\V1\Actions\Faculty;
use App\Models\Faculty;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for loading a single faculty record.
 */
class ShowFaculty
{
    /**
     * @param  Faculty $faculty
     * @return Faculty
     */
    public function handle(Faculty $faculty): Faculty
    {
        try {
            return $faculty->load('university');
        } catch (Throwable $exception) {
            Log::error('ShowFaculty failed', ['faculty_id' => $faculty->id, 'error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw $exception;
        }
    }
}
