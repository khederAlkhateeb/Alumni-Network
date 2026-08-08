<?php

namespace App\Http\Requests\MentorshipProgram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMentorshipProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'mentor_per_mentees_max' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'closed'])],
        ];
    }
}
