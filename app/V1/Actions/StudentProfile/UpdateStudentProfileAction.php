<?php

namespace App\V1\Actions\StudentProfile;

use App\Models\StudentProfile;
use App\Services\UploadFileService;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateStudentProfileAction
{
    public function __construct(
        private readonly UploadFileService $uploadFileService,
    ) {}

    /**
     * Update student profile fields + photo (optional)
     *
     * @param StudentProfile $profile
     * @param array<string, mixed> $data
     * @return StudentProfile
     * @throws Throwable
     */
    public function handle(StudentProfile $profile, array $data): StudentProfile
    {
        return DB::transaction(function () use ($profile, $data) {

            // 1. Update basic fields
            $profile->update([
                'major_id'                 => $data['major_id'] ?? $profile->major_id,
                'enrollment_number'        => $data['enrollment_number'] ?? $profile->enrollment_number,
                'enrollment_year'          => $data['enrollment_year'] ?? $profile->enrollment_year,
                'expected_graduation_year' => $data['expected_graduation_year'] ?? $profile->expected_graduation_year,
            ]);

            // 2. Delete photo if flag is set (deals safely with strings "true", "1", true)
            $shouldDeletePhoto = filter_var($data['delete_photo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($shouldDeletePhoto) {
                $this->deleteExistingPhoto($profile);
            }

            // 3. Upload new photo if provided
            if (isset($data['photo'])) {
                // Delete old photo if exists
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

            // 4. Return fresh instance with relations
            return $profile->fresh(['major', 'photo']);
        });
    }

    /**
     * Helper method to delete existing photo safely via UploadFileService.
     */
    private function deleteExistingPhoto(StudentProfile $profile): void
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
