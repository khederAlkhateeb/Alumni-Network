<?php

namespace App\V1\Actions\AlumniProfile\WorkExperience;
use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\ModelNotFoundException;



class DestroyWorkExperienceAction
{
    public function execute(AlumniProfile $profile, int $workExperienceId): void
    {
        $workExperience = $profile->workExperiences()->find($workExperienceId);

        if (! $workExperience) {
            throw new ModelNotFoundException("Work experience not found in your profile.");
        }

        $workExperience->delete();
    }
}
