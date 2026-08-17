<?php

namespace App\V1\Actions\AlumniProfile\WorkExperience;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Support\Facades\DB;

/**
 * Class StoreWorkExperienceAction
 *
 * Handles creating a new work experience for an alumni profile and conditionally
 * updates the profile's primary job headline based on user preference.
 */
class StoreWorkExperienceAction
{
    /**
     * Execute the work experience creation process.
     *
     * @param AlumniProfile $profile The alumni profile receiving the experience.
     * @param array $data Validated payload containing job details and optional headline flag.
     * @return WorkExperience The newly created work experience instance.
     *
     * @throws \Throwable If database transaction fails.
     */
    public function handle(AlumniProfile $profile, array $data): WorkExperience
    {
        return DB::transaction(function () use ($profile, $data) {


            $experience = $profile->workExperiences()->create([
                'job_title'    => $data['job_title'],
                'company' => $data['company'],
                'start_date'   => $data['start_date'],
                'end_date'     =>  ($data['end_date'] ?? null),
                         ]);

            if (!empty($data['set_as_primary_headline']) && $data['set_as_primary_headline'] === true) {
                $profile->update([
                    'current_job_title' => $experience->job_title,
                    'current_company'   => $experience->company,

                ]);
            }

            return $experience;
        });
    }
}
