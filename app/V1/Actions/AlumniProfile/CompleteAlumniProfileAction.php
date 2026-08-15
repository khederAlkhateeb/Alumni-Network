<?php
namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Services\UploadFileService;


    /**
     * Execute profile completion update.
     */
   class CompleteAlumniProfileAction
{
    public function __construct(
        private readonly UploadFileService $uploadFileService
    ) {}

    public function execute(AlumniProfile $profile, array $data): AlumniProfile
    {
        $profile->update($data);
        if (isset($data['photo'])) {
            $uploadResult = $this->uploadFileService->upload(
                $data['photo'],
                (string) $profile->user_id
            );
            $profile->photo()->create([
                'file_path' => $uploadResult['safe_filename'],
            ]);
        }
        return $profile->fresh(['skills', 'workExperiences', 'photo']);
    }
}




















