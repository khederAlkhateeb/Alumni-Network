<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Handles validation rules and authorization for resetting a user's password.
 *
 * @package App\Http\Requests\Api\V1\Authentication
 */
class ResetPasswordRequest extends FormRequest
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
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
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
            'token.required'     => 'A password reset token is required.',
            'token.string'       => 'The password reset token must be a valid string.',

            'email.required'     => 'An email address is required.',
            'email.email'        => 'Please provide a valid email address.',
            'email.exists'       => 'We could not find an account with that email address.',

            'password.required'  => 'A new password is required.',
            'password.string'    => 'The new password must be a valid string.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
