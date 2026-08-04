<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
|--------------------------------------------------------------------------
| Event Resource
|--------------------------------------------------------------------------
|
| Transforms an Event model into a consistent JSON structure for API
| responses. Includes the registrations count only when it has been
| eager-loaded (e.g. via loadCount('registrations')) to avoid extra
| queries on endpoints that don't need it.
|
*/

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'university_id'       => $this->university_id,
            'title'               => $this->title,
            'description'         => $this->description,
            'type'                => $this->type,
            'location'            => $this->location,
            'meeting_link'        => $this->meeting_link,
            'start_date'          => $this->start_date,
            'end_date'            => $this->end_date,
            'capacity'            => $this->capacity,
            'status'              => $this->status,
            'registrations_count' => $this->whenCounted('registrations'),
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}
