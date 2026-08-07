<?php

namespace App\Http\Requests\MentorshipProgram;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMentorshipProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', Rule::when($this->filled('start_date'), ['after_or_equal:start_date'])],
            'mentor_per_mentees_max' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'active', 'closed'])],
        ];
    }
}
