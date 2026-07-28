<?php

namespace App\V1\Actions\AlumniProfile\WorkExperience;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class DestroyWorkExperienceAction
 *
 * Handles the deletion of a specific work experience record associated with an alumni profile.
 * Ensures the record belongs directly to the target profile before removal.
 *
 * @package App\V1\Actions\AlumniProfile\WorkExperience
 */
class DestroyWorkExperienceAction
{
    /**
     * Execute the work experience deletion logic.
     *
     * @param AlumniProfile $profile The target alumni profile model.
     * @param int $workExperienceId The ID of the work experience entry to delete.
     * @return void
     *
     * @throws ModelNotFoundException If the work experience record does not belong to the profile or does not exist.
     */
    public function execute(AlumniProfile $profile, int $workExperienceId): void
    {
        $workExperience = $profile->workExperiences()->find($workExperienceId);

        if (! $workExperience) {
            throw new ModelNotFoundException("Work experience not found in your profile.");
        }

        $workExperience->delete();
    }
}
