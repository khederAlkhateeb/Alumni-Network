<?php

namespace App\Http\Requests\Api\V1\Notifications;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Index Notification Request
|--------------------------------------------------------------------------
|
| Validates the optional query parameters accepted by
| GET /notifications. Authentication and per-user scoping are
| already enforced upstream by the 'auth:sanctum' middleware and
| $user->notifications() (Rule 5.1: User Scoping), so this request
| is only responsible for validating pagination input.
|
*/

class IndexNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Any authenticated user may list their own notifications.
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
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
            'page.integer' => 'Page must be a valid page number.',
            'page.min'     => 'Page must be at least 1.',
        ];
    }
}
