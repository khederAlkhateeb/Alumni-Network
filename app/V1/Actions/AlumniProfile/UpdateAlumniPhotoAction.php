<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Models\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateAlumniPhotoAction
{
    public function execute(AlumniProfile $profile, UploadedFile $photo): Attachment
    {/**
     * Delete the old profile photo (if exists) and store the new one.
     *
     * @param AlumniProfile $profile
     * @param UploadedFile $photo
     * @return Attachment
     */

        $existingAttachment = $profile->photo;

        if ($existingAttachment) {
            Storage::disk('public')->delete($existingAttachment->file_path);
            $existingAttachment->delete();
        }

        $path = $photo->store('alumni-photos', 'public');

        return $profile->photo()->create([
            'file_path' => $path,
        ]);
    }
}
