<?php
namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;

class ToggleMentorAvailabilityAction
{
    public function execute(AlumniProfile $profile): AlumniProfile
    {
        /**
     * Toggle the `is_open_to_mentor` flag and save the profile.
     *
     * @param AlumniProfile $profile
     * @return AlumniProfile
     */
    //use save better
        $profile->update([
            'is_open_to_mentor' => ! $profile->is_open_to_mentor,
        ]);

        return $profile;
    }
}
