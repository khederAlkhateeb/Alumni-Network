<?php

namespace App\Http\Resources;

use App\Models\MentorshipRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableMentorResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $programId = MentorshipRequest::where('mentor_id', $this->id)->value('program_id');

        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'program_id'   => $programId,
       // 'program'      => new MentorshipProgramResource($this->whenLoaded('mentorshipPrograms')->first()),
            'is_available' => !$this->hasReachedLimit($programId),
        ];
    }





    }

