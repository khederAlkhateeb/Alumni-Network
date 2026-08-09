<?php

namespace App\V1\Actions\MentorshipProgram;

use App\Models\MentorshipProgram;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateMentorshipProgramAction
{
    public function handle(MentorshipProgram $program, array $data): MentorshipProgram
    {
        try {
            $program->update($data);
            return $program->refresh();
        } catch (Throwable $exception) {
            Log::error('UpdateMentorshipProgramAction failed', [
                'program_id' => $program->id,
                'data' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}
