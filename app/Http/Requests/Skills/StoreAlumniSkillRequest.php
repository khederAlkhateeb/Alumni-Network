<?php

namespace App\Http\Requests\Skills;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlumniSkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
         'skills'               => ['required', 'array', 'min:1'],
        'skills.*.name'        => ['required', 'string', 'max:100'],
        'skills.*.category' => ['required', 'string','max:100' ],
        ];
    }

    /**
     * Custom error messages for skill creation validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'   => 'Skill name is required.',
            'name.string'     => 'Skill name must be a valid text string.',
            'name.max'        => 'Skill name cannot exceed 255 characters.',

            'category.string' => 'Skill category must be a valid text string.',
            'category.max'    => 'Skill category cannot exceed 255 characters.',
        ];
    }
}

