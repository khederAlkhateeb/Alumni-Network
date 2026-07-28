<?php

namespace App\Http\Requests\WorkExperience;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateWorkExperience
 *
 * Handles validation rules and custom failure messages for
 * updating an existing work experience entry.
 *
 * @property-read string|null $company
 * @property-read string|null $job_title
 * @property-read string|null $start_date
 * @property-read string|null $end_date
 * @property-read bool|null $set_as_primary_headline
 *
 * @package App\Http\Requests\WorkExperience
 */
class UpdateWorkExperience extends FormRequest
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
            'company'                 => ['nullable', 'string', 'max:255'],
            'job_title'               => ['nullable', 'string', 'max:255'],
            'start_date'              => ['nullable', 'date', 'before_or_equal:today'],
            'end_date'                => ['nullable', 'date', 'after:start_date'],
            'set_as_primary_headline' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom error messages for updating work experience validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'company.string'                   => 'Company name must be a valid text string.',
            'company.max'                      => 'Company name cannot exceed 255 characters.',

            'job_title.string'                 => 'Job title must be a valid text string.',
            'job_title.max'                    => 'Job title cannot exceed 255 characters.',

            'start_date.date'                  => 'Start date must be a valid date format.',
            'start_date.before_or_equal'       => 'Start date cannot be in the future.',

            'end_date.date'                    => 'End date must be a valid date format.',
            'end_date.after'                   => 'End date must be after the start date.',

            'set_as_primary_headline.boolean'  => 'Set as primary headline must be true or false.',
        ];
    }
}
