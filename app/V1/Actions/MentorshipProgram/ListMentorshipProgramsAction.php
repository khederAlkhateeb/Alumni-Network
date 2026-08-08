<?php

namespace App\V1\Actions\MentorshipProgram;

use App\Models\MentorshipProgram;
use Illuminate\Support\Facades\Log;
use Throwable;

class ListMentorshipProgramsAction
{
    public function handle(int $universityId, int $perPage = 20)
    {
        try {
            return MentorshipProgram::query()
                ->where('university_id', $universityId)
                ->orderBy('start_date', 'desc')
                ->get();
        } catch (Throwable $exception) {
            Log::error('ListMentorshipProgramsAction failed', [
                'university_id' => $universityId,
                'per_page' => $perPage,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}
