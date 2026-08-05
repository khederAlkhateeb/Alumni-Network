<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MentorshipRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'program_id'    => $this->program_id,
            'intro_message' => $this->intro_message,
            'status'        => $this->status->value,
            'status_label'  => $this->status,
               // 'program'       => new MentorshipProgramResource($this->whenLoaded('program')),
            'mentor'        => $this->whenLoaded('mentor'),
            'mentee'        => $this->whenLoaded('mentee'),

            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
