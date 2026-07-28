<?php

namespace App\V1\Actions\Faculty;

use App\Models\Faculty;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for creating a new faculty record.
 */
class CreateFaculty
{
    /**
     * @param  array{name: string, university_id: int} $data
     * @return Faculty
     */
    public function handle(array $data): Faculty
    {
        try {
            $faculty = Faculty::create([
                'name' => $data['name'],
                'university_id' => $data['university_id'],
            ]);
            return $faculty->fresh();
        } catch (Throwable $exception) {
            Log::error('CreateFaculty failed', ['payload' => $data, 'error' => $exception->getMessage(), 'trace' => $exception->getTraceAsString()]);
            throw $exception;
        }
    }
}
