<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
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
            'status' => $this->status,
            $this->mergeWhen($this->sender->id != $request->user()->id, [
                'sender' => $this->whenLoaded('sender', fn() => [
                    'id' => $this->sender->id,
                    'name' => $this->sender->name,
                    'role' => $this->sender->role_label,
                ], []),
            ]),
             $this->mergeWhen($this->receiver->id != $request->user()->id, [
                'receiver' => $this->whenLoaded('receiver', fn() => [
                    'id' => $this->receiver->id,
                    'name' => $this->receiver->name,
                    'role' => $this->receiver->role_label,
                ], []),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
