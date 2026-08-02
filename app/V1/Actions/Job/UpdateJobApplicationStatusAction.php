<?php

namespace App\V1\Actions\Job;

use App\Models\JobApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Updates the status of a job application.
 *
 * It validates the incoming status against the allowed set before persisting the
 * change and returning the refreshed model.
 */
class UpdateJobApplicationStatusAction
{
    /**
     * Change the application status to the provided value.
     *
     * @param JobApplication $application The application to update.
     * @param string $status The new status value.
     *
     * @return JobApplication
     *
     * @throws ValidationException
     */
    public function handle(JobApplication $application, string $status): JobApplication
    {
        if (! in_array($status, JobApplication::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid application status provided.'],
            ]);
        }

        try {
            $application->status = $status;
            $application->save();

            return $application->refresh();
        } catch (Throwable $exception) {
            Log::error('UpdateJobApplicationStatusAction failed', [
                'application_id' => $application->id,
                'status' => $status,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
