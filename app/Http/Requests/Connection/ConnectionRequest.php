<?php

namespace App\Http\Requests\Connection;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConnectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Inject the resolved route binding into the validated payload
     * before the rules are evaluated.
     */
    protected function prepareForValidation(): void
    {
        $receiver = $this->route('user');

        $this->merge([
            'requester_id' => $this->user()?->id,
            'receiver_id' => $receiver instanceof User ? $receiver->id : $receiver,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requester_id' => ['required', 'integer', 'exists:users,id'],
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:requester_id'],
        ];
    }
}
