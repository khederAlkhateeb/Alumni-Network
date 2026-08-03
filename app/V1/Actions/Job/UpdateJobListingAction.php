<?php

namespace App\V1\Actions\Job;

use App\Models\JobListing;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Updates an existing job listing.
 *
 * It applies only the provided fields while preserving the current values for any
 * omitted attributes, and logs failures for monitoring.
 */
class UpdateJobListingAction
{
    /**
     * Update the target job listing with the submitted payload.
     *
     * @param JobListing $jobListing The listing being updated.
     * @param array $data The validated data for the update operation.
     *
     * @return JobListing
     */
    public function handle(JobListing $jobListing, array $data): JobListing
    {
        try {
            $jobListing->update([
                'title' => $data['title'] ?? $jobListing->title,
                'company' => $data['company'] ?? $jobListing->company,
                'location' => $data['location'] ?? $jobListing->location,
                'type' => $data['type'] ?? $jobListing->type,
                'description' => $data['description'] ?? $jobListing->description,
                'requirements' => $data['requirements'] ?? $jobListing->requirements,
                'salary_range' => $data['salary_range'] ?? $jobListing->salary_range,
                'expires_at' => $data['expires_at'] ?? $jobListing->expires_at,
                'status' => $data['status'] ?? $jobListing->status,
                'university_id' => $data['university_id'] ?? $jobListing->university_id,
            ]);

            return $jobListing;
        } catch (Throwable $exception) {
            Log::error('UpdateJobListingAction failed', [
                'job_listing_id' => $jobListing->id,
                'payload' => $data,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
