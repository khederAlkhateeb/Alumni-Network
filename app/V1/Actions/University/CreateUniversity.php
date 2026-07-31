<?php

namespace App\V1\Actions\University;

use App\Models\University;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action responsible for creating a new university record
 * and securely handling associated logo uploads.
 */
class CreateUniversity
{
    /**
     * @param UploadFileService $uploadFileService
     */
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Handle the university creation logic.
     *
     * @param  array<string, mixed> $data The validated request data for creation.
     *
     * @return University The newly created university instance.
     * @throws Throwable If file upload or database creation fails.
     */
    public function handle(array $data): University
    {
        try {
            $userId = (string) auth()->id();

            // 1. Prepare the base creation payload
            $universityPayload = [
                'name'       => $data['name'],
                'country'    => $data['country'],
                'website'    => $data['website'],
                'created_by' => $userId,
            ];

            // 2. Safely check if a logo was provided in the payload
            if (isset($data['logo'])) {
                // Upload the file and capture the returned result
                $uploadResult = $this->uploadFileService->upload($data['logo'], $userId);

                // Assign the newly generated safe filename to the database payload
                $universityPayload['logo'] = $uploadResult['safe_filename'];
            }

            // 3. Create the university record and return it directly
            return University::create($universityPayload);

        } catch (Throwable $exception) {
            Log::error('CreateUniversity failed', [
                'payload' => $data,
                'error'   => $exception->getMessage(),
                'trace'   => $exception->getTraceAsString()
            ]);

            throw $exception;
        }
    }
}
