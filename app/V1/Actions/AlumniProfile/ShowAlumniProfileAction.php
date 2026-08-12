<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use Cache;
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
                'photo'
            ])
            ->findOrFail($alumniProfileId);


        $skills = Cache::rememberForever("alumni_skills_{$alumniProfileId}", function () use ($profile) {
            return $profile->skills()->get();
        });


        $profile->setRelation('skills', $skills);

        return $profile;
    }
    }

