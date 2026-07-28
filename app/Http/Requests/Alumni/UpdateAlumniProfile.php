<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlumniProfile extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $linkedin = $this->input('linkedin_url');

        if ($linkedin) {
            $linkedin = trim($linkedin);
            if (! str_starts_with($linkedin, 'http://') && ! str_starts_with($linkedin, 'https://')) {
                $linkedin = 'https://' . $linkedin;
            }

            $this->merge([
                'linkedin_url' => $linkedin,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'bio'               => ['nullable', 'string', 'max:1000'],
            'graduation_year'   => ['sometimes', 'integer', 'digits:4', 'min:1950', 'max:' . date('Y')],
            'current_job_title' => ['nullable', 'string', 'max:255'],
            'current_company'   => ['nullable', 'string', 'max:255'],
            'country'           => ['nullable', 'string', 'max:100'],
            'city'              => ['nullable', 'string', 'max:100'],
            'linkedin_url'      => ['nullable', 'url', 'max:255'],
            'is_open_to_mentor' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Custom error messages for form validation.
     */
    public function messages(): array
    {
        return [
            'bio.string'                 => 'The bio must be a valid text string.',
            'bio.max'                    => 'The bio cannot exceed 1000 characters.',

            'graduation_year.integer'    => 'Graduation year must be a valid number.',
            'graduation_year.digits'     => 'Graduation year must be a 4-digit year.',
            'graduation_year.min'        => 'Graduation year cannot be earlier than 1950.',
            'graduation_year.max'        => 'Graduation year cannot be in the future.',

            'current_job_title.string'   => 'Current job title must be a valid text string.',
            'current_job_title.max'      => 'Current job title cannot exceed 255 characters.',

            'current_company.string'     => 'Current company name must be a valid text string.',
            'current_company.max'        => 'Current company name cannot exceed 255 characters.',

            'country.string'             => 'Country name must be a valid text string.',
            'country.max'                => 'Country name cannot exceed 100 characters.',

            'city.string'                => 'City name must be a valid text string.',
            'city.max'                   => 'City name cannot exceed 100 characters.',

            'linkedin_url.url'           => 'Please enter a valid LinkedIn URL (e.g. https://linkedin.com/in/username).',
            'linkedin_url.max'           => 'LinkedIn URL cannot exceed 255 characters.',

            'is_open_to_mentor.boolean'  => 'Mentorship availability must be true or false.',
        ];
    }
}
