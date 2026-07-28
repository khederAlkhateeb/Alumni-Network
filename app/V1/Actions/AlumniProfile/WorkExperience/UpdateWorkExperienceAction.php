<?php

namespace App\V1\Actions\AlumniProfile\WorkExperience;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Handles updating an existing work experience record with strict date validation & profile scoping.
 */
class UpdateWorkExperienceAction
{
    /**
     * Execute the work experience update action.
     *
     * @param AlumniProfile $profile
     * @param int $workExperienceId
     * @param array{
     *     company?: string,
     *     job_title?: string,
     *     start_date?: string,
     *     end_date?: string|null
     * } $data
     * @return WorkExperience
     * @throws ModelNotFoundException|ValidationException
     */
    public function execute(AlumniProfile $profile, int $workExperienceId, array $data): WorkExperience
    {
       
        $workExperience = $profile->workExperiences()->find($workExperienceId);

        if (! $workExperience) {
            throw new ModelNotFoundException("Work experience not found in your profile.");
        }

        $effectiveStartDate = $data['start_date'] ?? $workExperience->start_date?->toDateString();

        $effectiveEndDate = array_key_exists('end_date', $data)
            ? $data['end_date']
            : $workExperience->end_date?->toDateString();


        if ($effectiveEndDate && $effectiveStartDate) {
            $start = Carbon::parse($effectiveStartDate);
            $end   = Carbon::parse($effectiveEndDate);

            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages([
                    'end_date' => 'End date must be after the start date.',
                ]);
            }
        }


        $workExperience->fill([
            'company'    => $data['company'] ?? $workExperience->company,
            'job_title'  => $data['job_title'] ?? $workExperience->job_title,
            'start_date' => $effectiveStartDate,
            'end_date'   => $effectiveEndDate,
        ]);

        $workExperience->save();

        return $workExperience->fresh();
    }
}
