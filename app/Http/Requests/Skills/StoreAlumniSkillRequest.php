<?php

namespace App\Http\Requests\Skills;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreAlumniSkillRequest
 *
 * Handles validation rules and custom failure messages for
 * attaching/creating multiple skills for an alumni profile.
 *
 * @property-read array<int, array{name: string, category: string}> $skills
 *
 * @package App\Http\Requests\Skills
 */
class StoreAlumniSkillRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'skills'            => ['required', 'array', 'min:1'],
            'skills.*.name'     => ['required', 'string', 'max:100'],
            'skills.*.category' => ['required', 'string', 'max:100'],
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
            'skills.required'          => 'At least one skill must be provided.',
            'skills.array'             => 'Skills must be formatted as an array.',
            'skills.min'               => 'Please provide at least one skill to insert.',

            'skills.*.name.required'     => 'Skill name is required.',
            'skills.*.name.string'       => 'Skill name must be a valid text string.',
            'skills.*.name.max'          => 'Skill name cannot exceed 100 characters.',

            'skills.*.category.required' => 'Skill category is required.',
            'skills.*.category.string'   => 'Skill category must be a valid text string.',
            'skills.*.category.max'      => 'Skill category cannot exceed 100 characters.',
        ];
    }
}
