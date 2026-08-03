<?php

namespace App\Http\Requests\Api\V1\Events;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Record Attendance Request
|--------------------------------------------------------------------------
|
| Validates the payload used to mark a registered user as having
| attended an event. Authorization is delegated to the EventPolicy
| (only the University Admin who owns the event's university may
| record attendance).
|
*/

class RecordAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('event'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'The user ID is required.',
            'user_id.integer'  => 'The user ID must be a valid number.',
            'user_id.exists'   => 'The specified user does not exist.',
        ];
    }
}
