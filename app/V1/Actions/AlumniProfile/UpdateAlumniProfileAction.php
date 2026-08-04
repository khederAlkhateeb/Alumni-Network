<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateAlumniProfileAction
{
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Update profile data + photo in one unified action.
     *
     * @param AlumniProfile $profile
     * @param array<string, mixed> $data
     * @return AlumniProfile
     * @throws Throwable
     */
    public function execute(AlumniProfile $profile, array $data): AlumniProfile
    {
        try {
            return DB::transaction(function () use ($profile, $data) {
                // 1. Fill basic profile fields
                $profile->fill([
                    'current_job_title' => $data['current_job_title'] ?? $profile->current_job_title,
                    'current_company'   => $data['current_company'] ?? $profile->current_company,
                    'linkedin_url'      => $data['linkedin_url'] ?? $profile->linkedin_url,
                    'bio'               => $data['bio'] ?? $profile->bio,
                    'city'              => $data['city'] ?? $profile->city,
                    'country'           => $data['country'] ?? $profile->country,
                ]);

                // 2. Handle explicit photo deletion
                $shouldDeletePhoto = filter_var($data['delete_photo'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if ($shouldDeletePhoto) {
                    $this->deleteExistingPhoto($profile);
                }

                // 3. Handle photo update
                if (isset($data['photo'])) {
                    // Delete old photo first if exists
                    $this->deleteExistingPhoto($profile);

                    // Upload new photo using profile owner's ID
                    $uploadResult = $this->uploadFileService->upload(
                        $data['photo'],
                        (string) $profile->user_id
                    );

                    // Save new photo record
                    $profile->photo()->create([
                        'file_path' => $uploadResult['safe_filename'],
                    ]);
                }

                // 4. Save profile updates
                $profile->save();

                return $profile->fresh(['user', 'major', 'photo']);
            });

        } catch (Throwable $exception) {
            Log::error('UpdateAlumniProfileAction failed', [
                'profile_id' => $profile->id,
                'payload'    => $data,
                'error'      => $exception->getMessage(),
                'trace'      => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Helper method to delete existing photo safely via UploadFileService.
     */
    private function deleteExistingPhoto(AlumniProfile $profile): void
    {
        $existingAttachment = $profile->photo;

        if ($existingAttachment && $existingAttachment->file_path) {
       
            $this->uploadFileService->deleteFile(
                $existingAttachment->file_path,
                (string) $profile->user_id
            );


            $existingAttachment->delete();
        }
    }
}
