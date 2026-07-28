<?php

namespace App\V1\Actions\AlumniProfile\Skill;

use App\Models\AlumniProfile;

class DestroyAlumniSkillAction
{
    public function execute(AlumniProfile $profile, int $skillId): void
    {
        $exists = $profile->skills()->where('skills.id', $skillId)->exists();

    if (! $exists) {
        throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
            "Skill with ID {$skillId} is not attached to your profile."
        );
    }
        $profile->skills()->detach($skillId);
    }
}
