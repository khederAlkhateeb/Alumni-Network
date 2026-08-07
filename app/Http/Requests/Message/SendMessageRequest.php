<?php

namespace App\Http\Requests\Message;

use App\Models\Message;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Active-status check happens here (consistent with Post/Comment/
     * Reaction policies). Whether the sender is actually allowed to
     * message this specific receiver (accepted Connection or Mentorship)
     * is a business rule checked inside SendMessageAction itself, since
     * it depends on the receiver_id being validated first.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:' . $this->user()?->id],
            'content'     => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.required' => 'A receiver must be specified.',
            'receiver_id.exists'   => 'The selected receiver does not exist.',
            'receiver_id.different' => 'You cannot send a message to yourself.',
            'content.required'     => 'Message content is required.',
            'content.max'          => 'Message content must not exceed :max characters.',
        ];
    }
}
