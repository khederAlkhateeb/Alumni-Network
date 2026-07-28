<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateAlumniProfile
 *
 * Handles validation, input sanitization, and error messaging
 * for updating an alumni's profile information.
 *
 * @property-read string|null $bio
 * @property-read int|null $graduation_year
 * @property-read string|null $current_job_title
 * @property-read string|null $current_company
 * @property-read string|null $country
 * @property-read string|null $city
 * @property-read string|null $linkedin_url
 * @property-read bool|null $is_open_to_mentor
 *
 * @package App\Http\Requests\Alumni
 */
class UpdateAlumniProfile extends FormRequest
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
     * Prepare inputs for validation.
     *
     * Sanitizes and normalizes the LinkedIn URL by prepending the HTTPS scheme if missing.
     *
     * @return void
     */
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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
     *
     * @return array<string, string>
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
