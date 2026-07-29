<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Handles validation rules and authorization for changing a user's password.
 *
 * @package App\Http\Requests\Api\V1\Authentication
 */
class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Your current password is required.',
            'current_password.string'   => 'The current password must be a valid string.',

            'password.required'         => 'A new password is required.',
            'password.string'           => 'The new password must be a valid string.',
            'password.confirmed'        => 'The new password confirmation does not match.',
        ];
    }
}
