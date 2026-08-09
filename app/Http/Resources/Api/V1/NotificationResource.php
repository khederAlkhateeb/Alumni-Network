<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
|--------------------------------------------------------------------------
| Notification Resource
|--------------------------------------------------------------------------
|
| Transforms a Laravel DatabaseNotification model into a consistent
| JSON structure. "data" holds the notification's own payload (as
| defined by each Notification class's toArray() method), while
| "type" exposes the short class name for client-side handling.
|
*/

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => $this->type,
            'message'    => $this->message,
            'related'    => [
                'type' => $this->related_type ? class_basename($this->related_type) : null,
                'id'   => $this->related_id,
            ],
            'read_at'    => $this->read_at,
            'is_read'    => !is_null($this->read_at),
            'created_at' => $this->created_at,
        ];
    }
}
