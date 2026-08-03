<?php

namespace App\V1\Actions\Job;

use App\Models\JobListing;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Closes an active job listing.
 *
 * This action marks a listing as closed and refreshes the model so the updated
 * state is returned immediately to the caller.
 */
class CloseJobListingAction
{
    /**
     * Set the provided job listing status to closed.
     *
     * @param JobListing $jobListing The listing to close.
     *
     * @return JobListing
     */
    public function handle(JobListing $jobListing): JobListing
    {
        try {
            $jobListing->status = JobListing::STATUS_CLOSED;
            $jobListing->save();

            return $jobListing->refresh();
        } catch (Throwable $exception) {
            Log::error('CloseJobListingAction failed', [
                'job_listing_id' => $jobListing->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
