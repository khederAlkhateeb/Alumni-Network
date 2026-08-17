<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Services\UploadFileService;

/**
 * Handles completing an alumni profile, updating its details, uploading a photo, and activating the status.
 */
class CompleteAlumniProfileAction
{
    /**
     * Create a new action instance.
     *
     * @param UploadFileService $uploadFileService
     */
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Execute profile completion update and activate alumni status.
     *
     * @param AlumniProfile $profile
     * @param array{
     *     bio?: string,
     *     linkedin_url?: string,
     *     city?: string,
     *     country?: string,
     *     current_job_title?: string,
     *     current_company?: string,
     *     photo?: mixed,
     *     [key: string]: mixed
     * } $data
     * @return AlumniProfile
     */
    public function handle(AlumniProfile $profile, array $data): AlumniProfile
    {
        $data['status'] = 'active';

        if (isset($data['photo'])) {
            $uploadResult = $this->uploadFileService->upload(
                $data['photo'],
                (string) $profile->user_id
            );

            $profile->photo()->Create(

                ['file_path' => $uploadResult['safe_filename']]
            );

            unset($data['photo']);
        }

        $profile->update($data);

        return $profile->fresh(['skills', 'workExperiences', 'photo']);
    }
}
