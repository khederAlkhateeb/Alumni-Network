<?php

namespace App\V1\Actions\MentorshipProgram;

use App\Models\MentorshipProgram;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateMentorshipProgramAction
{
    public function handle(int $universityId, array $data)
    {
        try {
            return MentorshipProgram::create(array_merge($data, [
                'university_id' => $universityId,
            ]));
        } catch (Throwable $exception) {
            Log::error('CreateMentorshipProgramAction failed', [
                'university_id' => $universityId,
                'data' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}
