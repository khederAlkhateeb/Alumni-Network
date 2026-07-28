<?php
namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;

class CompleteAlumniProfileAction
{
    /**
     * Execute profile completion update.
     */
    public function execute(AlumniProfile $profile, array $data): AlumniProfile
    {

        $profile->update($data);


        return $profile->fresh(['skills', 'workExperiences']);
    }
}
