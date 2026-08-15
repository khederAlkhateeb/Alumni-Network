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
        $currentUserId = $request->user()?->id;

        return [
            'id' => $this->id,
            'status' => $this->status,
// 
            $this->mergeWhen(
                $this->requester_id !== $currentUserId && $this->relationLoaded('sender'),
                fn () => [
                    'sender' => [
                        'id' => $this->sender?->id,
                        'name' => $this->sender?->name,
                        'role' => $this->sender?->role_label,
                    ],
                ]
            ),

            $this->mergeWhen(
                $this->receiver_id !== $currentUserId && $this->relationLoaded('receiver'),
                fn () => [
                    'receiver' => [
                        'id' => $this->receiver?->id,
                        'name' => $this->receiver?->name,
                        'role' => $this->receiver?->role_label,
                    ],
                ]
            ),

            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
