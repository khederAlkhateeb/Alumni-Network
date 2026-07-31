<?php

namespace App\V1\Actions\University;

use App\Models\University;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for updating an existing university record
 * and securely handling associated logo uploads.
 */
class UpdateUniversity
{
    /**
     * @param UploadFileService $uploadFileService
     */
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Handle the university update logic.
     *
     * @param  University           $university The model instance to update.
     * @param  array<string, mixed> $data       The validated request data.
     *
     * @return University
     * @throws Throwable
     */
    public function handle(University $university, array $data): University
    {
        try {
            $userId = (string) auth()->id();

            // 1. Prepare the base update payload using existing data as fallbacks
            $updatePayload = [
                'name'       => $data['name'] ?? $university->name,
                'country'    => $data['country'] ?? $university->country,
                'website'    => $data['website'] ?? $university->website,
                'updated_by' => $userId,
            ];

            // 2. Safely check if a logo was provided in the payload
            if (isset($data['logo'])) {
                // Upload the file and capture the returned result
                $uploadResult = $this->uploadFileService->upload($data['logo'], $userId);

                // Assign the newly generated safe filename to the database payload.
                // Change 'logo_path' to match your actual database column name if different.
                $updatePayload['logo'] = $uploadResult['safe_filename'];
            }

            // 3. Update the university record with the merged payload
            $university->update($updatePayload);

            return $university->fresh();

        } catch (Throwable $exception) {
            Log::error('UpdateUniversity failed', [
                'university_id' => $university->id,
                'payload'       => $data,
                'error'         => $exception->getMessage(),
                'trace'         => $exception->getTraceAsString()
            ]);

            throw $exception;
        }
    }
}
