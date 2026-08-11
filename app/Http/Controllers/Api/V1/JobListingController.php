<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Job\ApplyForJobRequest;
use App\Http\Requests\Job\StoreJobListingRequest;
use App\Http\Requests\Job\UpdateJobApplicationStatusRequest;
use App\Http\Requests\Job\UpdateJobListingRequest;
use App\Models\JobListing;
use App\Models\JobApplication;
use App\V1\Actions\Job\ApplyForJobAction;
use App\V1\Actions\Job\CloseJobListingAction;
use App\V1\Actions\Job\CreateJobListingAction;
use App\V1\Actions\Job\ListJobApplicationsAction;
use App\V1\Actions\Job\ListJobListingsAction;
use App\V1\Actions\Job\ListMyJobApplicationsAction;
use App\V1\Actions\Job\UpdateJobApplicationStatusAction;
use App\V1\Actions\Job\UpdateJobListingAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    /**
     * List job listings for authenticated users.
     */
    public function index(Request $request, ListJobListingsAction $listJobListings): JsonResponse
    {
        // Gate::authorize('view-jobs');

        $filters = $request->only(['university_id', 'type', 'status', 'posted_by_user_id']);
        $jobs = $listJobListings->handle($filters);

        return $this->successResponse(
            data: $jobs,
            message: 'Job listings retrieved successfully.',
        );
    }

    /**
     * Create a new job listing.
     */
    public function store(StoreJobListingRequest $request, CreateJobListingAction $createJobListing): JsonResponse
    {
        $job = $createJobListing->handle($request->validated(), $request->user()->id);

        return $this->successResponse(
            data: $job,
            message: 'Job listing created successfully.',
            code: 201,
        );
    }

    /**
     * Show a single job listing.
     */
    public function show(JobListing $jobListing): JsonResponse
    {
        // Gate::authorize('view-jobs');

        $jobListing->load(['university', 'postedBy']);

        return $this->successResponse(
            data: $jobListing,
            message: 'Job listing retrieved successfully.',
        );
    }

    /**
     * Update an existing job listing.
     */
    public function update(UpdateJobListingRequest $request, JobListing $jobListing, UpdateJobListingAction $updateJobListing): JsonResponse
    {
        // Gate::authorize('edit-own-job', $jobListing);

        $job = $updateJobListing->handle($jobListing, $request->validated());

        return $this->successResponse(
            data: $job,
            message: 'Job listing updated successfully.',
        );
    }

    /**
     * Delete a job listing.
     */
    public function destroy(JobListing $jobListing): JsonResponse
    {
        // Gate::authorize('delete-own-job', $jobListing);

        $jobListing->delete();

        return $this->successResponse(
            message: 'Job listing deleted successfully.',
        );
    }

    /**
     * Close a job listing so it is no longer available.
     */
    public function close(JobListing $jobListing, CloseJobListingAction $closeJobListing): JsonResponse
    {
        // Gate::authorize('close-job', $jobListing);

        $job = $closeJobListing->handle($jobListing);

        return $this->successResponse(
            data: $job,
            message: 'Job listing closed successfully.',
        );
    }

    /**
     * Apply to a job listing.
     */
    public function apply(ApplyForJobRequest $request, JobListing $jobListing, ApplyForJobAction $applyForJob): JsonResponse
    {
        // Gate::authorize('apply-for-job');

        $application = $applyForJob->handle($jobListing, $request->user()->id, $request->validated());

        return $this->successResponse(
            data: $application,
            message: 'Job application submitted successfully.',
            code: 201,
        );
    }

    /**
     * List applications for a specific job listing.
     */
    public function applications(JobListing $jobListing, ListJobApplicationsAction $listJobApplications): JsonResponse
    {
        // Gate::authorize('view-job-applications');

        $applications = $listJobApplications->handle($jobListing->id);

        return $this->successResponse(
            data: $applications,
            message: 'Job applications retrieved successfully.',
        );
    }

    /**
     * List applications submitted by the authenticated user.
     */
    public function myApplications(Request $request, ListMyJobApplicationsAction $listMyJobApplications): JsonResponse
    {
        $applications = $listMyJobApplications->handle();

        return $this->successResponse(
            data: $applications,
            message: 'Your job applications retrieved successfully.',
        );
    }

    /**
     * Update the status of a specific job application.
     */
    public function updateApplicationStatus(UpdateJobApplicationStatusRequest $request, JobListing $jobListing, JobApplication $application, UpdateJobApplicationStatusAction $updateApplicationStatus): JsonResponse
    {
        if ($application->job_listing_id !== $jobListing->id) {
            return $this->errorResponse('The application does not belong to the specified job listing.', [], 404);
        }

        // Gate::authorize('update-application-status', $application);

        $updated = $updateApplicationStatus->handle($application, $request->validated()['status']);

        return $this->successResponse(
            data: $updated,
            message: 'Job application status updated successfully.',
        );
    }
}
