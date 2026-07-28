<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use Illuminate\Support\Facades\Storage;

class DeleteAlumniPhotoAction
{
    public function execute(AlumniProfile $profile): void
    {
        /**
     * Delete the profile photo attachment if it exists.
     *
     * @param AlumniProfile $profile
     * @return void
     */
        $attachment = $profile->photo;

        if (! $attachment) {
            return;
        }

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();
    }
}
