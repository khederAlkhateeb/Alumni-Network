<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumniProfileListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->user->name,
            'graduation_year'   => $this->graduation_year,
            'current_job_title' => $this->current_job_title,
            'current_company'   => $this->current_company,
            'city'              => $this->city,
            'country'           => $this->country,
            'is_open_to_mentor' => (bool) $this->is_open_to_mentor,
            'major'             => $this->major?->name,
            'university'        => $this->major?->faculty?->university?->name,
            'completion_percentage' => $this->completeness_score,
        ];
    }
}
