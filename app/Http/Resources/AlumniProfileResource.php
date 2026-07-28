<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AlumniProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
   public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'bio' => $this->bio,
        'city' => $this->city,
        'completion_percentage' => $this->completeness_score,
        'photo_url' => $this->whenLoaded('photo', fn () => $this->photo
    ? Storage::disk('public')->url($this->photo->file_path)
    : null
),
        'skills' => SkillResource::collection($this->whenLoaded('skills')),
        'work_experiences' => WorkExperienceResource::collection($this->whenLoaded('workExperiences')),
    ];
}
}
