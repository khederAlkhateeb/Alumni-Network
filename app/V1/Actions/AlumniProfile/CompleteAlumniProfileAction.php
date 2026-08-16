<?php
namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Services\UploadFileService;

class CompleteAlumniProfileAction
{
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    /**
     * Execute profile completion update and activate alumni status.
     */
    public function handle(AlumniProfile $profile, array $data): AlumniProfile
    {

        $data['status'] = 'active';


        if (isset($data['photo'])) {
            $uploadResult = $this->uploadFileService->upload(
                $data['photo'],
                (string) $profile->user_id
            );

            $profile->photo()->updateOrCreate(
                ['alumni_profile_id' => $profile->id],
                ['file_path' => $uploadResult['safe_filename']]
            );


            unset($data['photo']);
        }


        $profile->update($data);

        return $profile->fresh(['skills', 'workExperiences', 'photo']);
    }
}
