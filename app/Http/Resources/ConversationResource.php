<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

        $otherUser = $this->user_one_id === $user->id
            ? $this->userTwo
            : $this->userOne;

        return [
            'conversation_id' => $this->id,
            'other_user'      => $otherUser,
            'last_message'    => $this->messages->first(),
            'unread_count'    => $this->unread_count,
        ];
    }
}
