<?php

namespace App\V1\Actions\AlumniProfile\Skill;

use App\Models\AlumniProfile;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class DestroyAlumniSkillAction
 *
 * Handles detaching a specific skill from an alumni profile,
 * ensuring the relationship exists prior to deletion.
 *
 * @package App\V1\Actions\AlumniProfile\Skill
 */
class DestroyAlumniSkillAction
{
    /**
     * Detach a skill from the given alumni profile.
     *
     * @param AlumniProfile $profile The target alumni profile instance.
     * @param int $skillId The ID of the skill to be detached.
     * @return void
     *
     * @throws ModelNotFoundException If the skill is not associated with the profile.
     */
    public function execute(AlumniProfile $profile, int $skillId): void
    {
        $exists = $profile->skills()
            ->where('skills.id', $skillId)
            ->exists();

        if (! $exists) {
            throw new ModelNotFoundException(
                "Skill with ID {$skillId} is not attached to your profile."
            );
        }

        $profile->skills()->detach($skillId);
    }
}
