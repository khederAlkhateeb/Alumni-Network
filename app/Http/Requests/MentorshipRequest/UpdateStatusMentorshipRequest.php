<?php

namespace App\Http\Requests\MentorshipRequest;

use App\Enums\MentorshipRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateStatusMentorshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                new Enum(MentorshipRequestStatus::class),

            ],
        ];
    }

    /**
     * Custom validation error messages in English.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The status field is required.',
            'status.enum'     => 'The selected status is invalid. Valid statuses are: pending, accepted, rejected, complete.',
        ];
    }
}
