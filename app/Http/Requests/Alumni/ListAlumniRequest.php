<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class ListAlumniRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'university_id'     => ['sometimes', 'integer', 'exists:universities,id'],
            'major_id'          => ['sometimes', 'integer', 'exists:majors,id'],
            'graduation_year'   => ['sometimes', 'integer', 'digits:4', 'min:1950', 'max:' . date('Y')],
            'skill_id'          => ['sometimes', 'integer', 'exists:skills,id'],
            'is_open_to_mentor' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'university_id.integer'     => 'The university ID must be a valid number.',
            'university_id.exists'      => 'The selected university does not exist.',

            'major_id.integer'          => 'The major ID must be a valid number.',
            'major_id.exists'           => 'The selected major does not exist.',

            'graduation_year.integer'   => 'Graduation year must be a valid number.',
            'graduation_year.digits'    => 'Graduation year must be a 4-digit year (e.g. 2024).',
            'graduation_year.min'       => 'Graduation year cannot be earlier than 1950.',
            'graduation_year.max'       => 'Graduation year cannot be in the future.',

            'skill_id.integer'          => 'The skill ID must be a valid number.',
            'skill_id.exists'           => 'The selected skill does not exist.',

            'is_open_to_mentor.boolean' => 'Mentorship status filter must be true or false.',
        ];
    }
}
