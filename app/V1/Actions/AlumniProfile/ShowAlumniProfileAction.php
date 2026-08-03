<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowAlumniProfileAction
{
    public function execute(int $alumniProfileId): AlumniProfile
    {
        /**
     * Find an active alumni profile by ID with preloaded relationships.
     *
     * @param int $alumniProfileId
     * @return AlumniProfile
     *
     * @throws ModelNotFoundException If the profile does not exist or is not active.
     */
        $profile = AlumniProfile::query()
            ->with([
                'user',
                'major.faculty.university',
                'workExperiences',
                'skills',
                'photo'
            ])
            ->find($alumniProfileId);

        return $profile;
    }
}
