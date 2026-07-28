<?php

namespace App\V1\Actions\AlumniProfile\Skill;

use App\Models\AlumniProfile;
use App\Models\Skill;

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
                // Search condition (Strictly by unique name to avoid integrity violations)
                ['name' => trim($skillData['name'])],

                // Attributes applied ONLY when creating a new record
                ['category' => $skillData['category'] ?? null]
            );

            return $skill->id;
        });

        // Attach the collected skill IDs to the alumni profile without removing existing ones.
        $profile->skills()->syncWithoutDetaching($skillIds);

        //  Reload relation to reflect immediate changes in the response.
        return $profile->load('skills');
    }
}
