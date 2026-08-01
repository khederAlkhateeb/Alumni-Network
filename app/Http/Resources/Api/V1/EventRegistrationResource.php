<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
|--------------------------------------------------------------------------
| Event Registration Resource
|--------------------------------------------------------------------------
|
| Transforms an EventRegistration model into a consistent JSON
| structure, including basic info about the registered user.
|
*/

class EventRegistrationResource extends JsonResource
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
            'id'            => $this->id,
            'event_id'      => $this->event_id,
            'user'          => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
            ],
            'registered_at' => $this->registered_at,
            'attended_at'   => $this->attended_at,
        ];
    }
}
