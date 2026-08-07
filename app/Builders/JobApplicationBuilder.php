<?php

namespace App\Builders;

use App\Models\JobApplication;
use App\Models\University;
use Illuminate\Database\Eloquent\Builder;

class JobApplicationBuilder extends Builder
{
    /**
     * Count job applications grouped by their statuses for a specific university.
     *
     * @param University|int $university The university instance or its ID.
     * @return array An associative array with statuses as keys and their respective counts as values.
     */
    public function countByStatusesForUniversity(University|int $university): array
    {
        $id = $university instanceof University ? $university->id : $university;
        $counts = [];

        foreach (JobApplication::STATUSES as $status) {
            $counts[$status] = (clone $this)
                ->whereHas('jobListing', fn ($query) => $query->forUniversity($id))
                ->where('status', $status)
                ->count();
        }

        return $counts;
    }
}