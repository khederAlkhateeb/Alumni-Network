<?php

namespace App\V1\Actions\AlumniProfile\Skill;

use App\Models\AlumniProfile;
use App\Models\Skill;
use Cache;

/**
 * Class StoreAlumniSkillsAction
 *
 * Handles attaching multiple skills to an alumni profile while ensuring
 * no duplicate skill names are created in the database.
 */
class StoreAlumniSkillsAction
{
    /**
     * Execute the skill assignment process for an alumni profile.
     *
     * @param AlumniProfile $profile The target alumni profile model.
     * @param array $data Validated input containing an array of skill objects.
     * @return AlumniProfile Updated profile with freshly loaded skills relation.
     */
    public function execute(AlumniProfile $profile, array $data): AlumniProfile
    {
        //  Iterate through the skills array, ensuring existing skills are fetched
        //    or new ones are created, and collect their primary keys (IDs).
        $skillIds = collect($data['skills'])->map(function (array $skillData) {

            $skill = Skill::firstOrCreate(

                ['name' => trim($skillData['name'])],

                ['category' => $skillData['category'] ?? null]
            );

            return $skill->id;
        });

        $profile->skills()->syncWithoutDetaching($skillIds);
       Cache::forget("alumni_skills_{$profile->id}");

        return $profile->load('skills');
    }
}
