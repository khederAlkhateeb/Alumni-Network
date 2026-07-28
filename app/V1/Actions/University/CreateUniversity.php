<?php

namespace App\V1\Actions\University;

use App\Models\University;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for creating a new university record.
 */
class CreateUniversity
{
    /**
     * Handle the university creation logic.
     *
     * @param  array $data The validated creation fields.
     * @return University
     * @throws Throwable
     */
    public function handle(array $data): University
    {
        try {
            $data['created_by'] = auth()->id();
            return University::create($data);
        } catch (Throwable $exception) {
            Log::error('CreateUniversity failed', [
                'payload' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);
            throw $exception;
        }
    }
}
