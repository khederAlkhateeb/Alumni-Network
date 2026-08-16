<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;
use App\Models\Skill;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShowAlumniProfileAction
{
    /**
     * Retrieve a specific alumni profile with eager-loaded relationships and cached skills.
     *
     * This action fetches the alumni profile details along with core relationships (`user`,
     * `major.faculty.university`, `workExperiences`, `photo`). To optimize database queries,
     * the profile's skills are retrieved from Redis/Cache storage via Cache Tags, hydrated
     * back into Eloquent Model instances, and manually attached to the profile relation.
     *
     * @param int $alumniProfileId The unique identifier of the alumni profile.
     *
     * @return AlumniProfile The fully loaded alumni profile model with skills attached.
     *
     * @throws ModelNotFoundException If no active alumni profile is found with the given ID.
     */
    public function handle(int $alumniProfileId): AlumniProfile
    {
        $profile = AlumniProfile::query()
            ->with([
                'user',
                'major.faculty.university',
                'workExperiences',
                'photo'
            ])
            ->findOrFail($alumniProfileId);

        $cachedSkills = Cache::tags(["alumni_profile_{$alumniProfileId}"])->remember(
            "alumni_skills_{$alumniProfileId}",
            now()->addHours(24),
            function () use ($profile) {
                return $profile->skills()->get()->toArray();
            }
        );

        $skills = Skill::hydrate($cachedSkills);
        $profile->setRelation('skills', $skills);

        return $profile;
    }
}
