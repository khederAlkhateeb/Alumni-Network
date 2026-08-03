<?php

namespace App\V1\Actions\Job;

use App\Models\JobListing;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Creates a new job listing posted by a user.
 *
 * This action centralizes the attributes required to persist a new job record
 * and records the creator through the authenticated user ID.
 */
class CreateJobListingAction
{
    /**
     * Store a new job listing in the database.
     *
     * @param array $data The validated job listing payload.
     * @param int $userId The authenticated user who created the listing.
     *
     * @return JobListing
     */
    public function handle(array $data, int $userId): JobListing
    {
        try {
            return JobListing::create([
                'title' => $data['title'],
                'company' => $data['company'],
                'location' => $data['location'] ?? null,
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'requirements' => $data['requirements'] ?? null,
                'salary_range' => $data['salary_range'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => $data['status'] ?? JobListing::STATUS_ACTIVE,
                'university_id' => $data['university_id'] ?? null,
                'posted_by_user_id' => $userId,
            ]);
        } catch (Throwable $exception) {
            Log::error('CreateJobListingAction failed', [
                'payload' => $data,
                'user_id' => $userId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }
}
