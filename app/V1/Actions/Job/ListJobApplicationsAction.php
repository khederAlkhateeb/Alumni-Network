<?php

namespace App\V1\Actions\Job;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lists applications submitted to a specific job listing.
 *
 * It returns the applications ordered by newest first and eager-loads the related
 * applicant and job listing data for efficient presentation.
 */
class ListJobApplicationsAction
{
    /**
     * Fetch all applications for a given job listing.
     *
     * @param int $jobListingId The ID of the job listing.
     *
     * @return Collection
     */
    public function handle(int $jobListingId): Collection
    {
        return JobApplication::query()
            ->where('job_listing_id', $jobListingId)
            ->with(['applicant', 'jobListing'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
