<?php

namespace App\V1\Actions\Job;

use App\Models\JobApplication;
use App\Models\JobListing;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Handles the application flow for a job listing.
 *
 * It validates that the listing is still active, prevents duplicate submissions,
 * blocks self-applications, and creates the job application record.
 */
class ApplyForJobAction
{
    /**
     * Creates a new job application for an applicant.
     *
     * @param JobListing $jobListing The job listing being applied to.
     * @param int $applicantId The authenticated applicant's user ID.
     * @param array $data The validation payload containing cover letter and resume data.
     *
     * @return JobApplication
     *
     * @throws ValidationException
     */
    public function handle(JobListing $jobListing, int $applicantId, array $data): JobApplication
    {
        if ($jobListing->status !== JobListing::STATUS_ACTIVE || ($jobListing->expires_at && $jobListing->expires_at->isPast())) {
            throw ValidationException::withMessages([
                'job_listing' => ['This job listing is no longer available.'],
            ]);
        }

        if (JobApplication::where('job_listing_id', $jobListing->id)
            ->where('applicant_id', $applicantId)
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'job_listing' => ['You have already applied to this job listing.'],
            ]);
        }

        if ($jobListing->posted_by_user_id === $applicantId) {
            throw ValidationException::withMessages([
                'job_listing' => ['You cannot apply to your own job listing.'],
            ]);
        }

        

        try {
            return JobApplication::create([
                'job_listing_id' => $jobListing->id,
                'applicant_id' => $applicantId,
                'cover_letter' => $data['cover_letter'] ?? null,
                'resume' => $data['resume'] ?? null,
                'status' => JobApplication::STATUS_SUBMITTED,
            ]);
        } catch (Throwable $exception) {
            Log::error('ApplyForJobAction failed', [
                'job_listing_id' => $jobListing->id,
                'applicant_id' => $applicantId,
                'payload' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
