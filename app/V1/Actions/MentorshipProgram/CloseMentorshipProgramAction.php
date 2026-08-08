<?php

namespace App\V1\Actions\MentorshipProgram;

use App\Models\MentorshipProgram;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

class CloseMentorshipProgramAction
{
    public function handle(MentorshipProgram $program): MentorshipProgram
    {
        try {
            return DB::transaction(function () use ($program) {
                $program->update(['status' => 'closed']);
                return $program->refresh();
            });
        } catch (Throwable $exception) {
            Log::error('CloseMentorshipProgramAction failed', [
                'program_id' => $program->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
            throw $exception;
        }
    }
}
