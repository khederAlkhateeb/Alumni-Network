<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkExperienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'company'    => $this->company,
            'job_title'  => $this->job_title,
            'start_date' => $this->start_date?->toDateString(),
            'end_date'   => $this->end_date?->toDateString(),
               'is_current'          => $this->is_current,
            'duration_in_months'  => $this->duration_in_months,
            'duration_label'      => $this->duration_label,
         
        ];
    }
}
