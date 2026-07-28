<?php

namespace App\V1\Actions\AlumniProfile;

use App\Models\AlumniProfile;

class UpdateAlumniProfileAction
{ /**
     * Update an alumni profile with partial data.
     *
     * Missing keys in $data keep their current value (partial update support).
     * Returns a fresh instance with 'user' and 'major' eager loaded to avoid
     * extra lazy-loaded queries when the result is passed to a Resource.
     *
     * @param  array<string, mixed>  $data  Validated fields to update.
     */

    public function execute(AlumniProfile $profile, array $data): AlumniProfile
    {
        $profile->fill([
            'current_job_title' => $data['current_job_title'] ?? $profile->current_job_title,
            'current_company'   => $data['current_company'] ?? $profile->current_company,
            'linkedin_url'      => $data['linkedin_url'] ?? $profile->linkedin_url,
            'bio'               => $data['bio'] ?? $profile->bio,
            'city'              => $data['city'] ?? $profile->city,
            'country'           => $data['country'] ?? $profile->country,
        ]);

        $profile->save();

        return $profile->fresh(['user', 'major']);
    }
}
