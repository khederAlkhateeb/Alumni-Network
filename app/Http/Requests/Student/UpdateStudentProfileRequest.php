<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class responsible for validating student profile update operations.
 *
 * This form request ensures:
 * - Only valid fields are accepted when updating a student profile.
 * - All provided fields follow strict validation rules.
 * - Custom error messages are returned for specific validation failures.
 *
 * Authorization:
 * - The request currently allows all authenticated users to attempt updating.
 *   (Actual permission checks should be handled via StudentProfilePolicy.)
 *
 * Validation Rules:
 * - major_id: must exist in majors table.
 * - enrollment_number: optional string identifier.
 * - enrollment_year: must be between 2000 and next year.
 * - expected_graduation_year: must be >= enrollment_year.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    /**
     * Determine whether the authenticated user is allowed to make this request.
     *
     * Note:
     * - This method returns true, meaning any authenticated user can submit the request.
     * - Actual authorization logic should be handled in StudentProfilePolicy::update().
     *
     * @return bool True if the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules applied to the request.
     *
     * Rules:
     * - major_id: optional, must be an integer and reference an existing major.
     * - enrollment_number: optional, nullable, max length 50.
     * - enrollment_year: optional, must be between 2000 and next calendar year.
     * - expected_graduation_year: optional, must be >= enrollment_year.
     *
     * @return array<string, mixed> The array of validation rules.
     */
    public function rules(): array
    {
        return [
            'major_id'                 => ['sometimes', 'integer', 'exists:majors,id'],
            'enrollment_number'        => ['sometimes', 'nullable', 'string', 'max:50'],
            'enrollment_year'          => ['sometimes', 'integer', 'min:2000', 'max:' . (date('Y') + 1)],
            'expected_graduation_year' => ['sometimes', 'integer', 'gte:enrollment_year'],
                   "photo" => ['sometimes','file','image', 'mimes:jpg,jpeg,png,webp' ,'nullable', 'max:5242880'],
                   'delete_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages for specific rule failures.
     *
     * @return array<string, string> The array of custom error messages.
     */
    public function messages(): array
    {
        return [
                 'photo.image'    => 'The uploaded file must be an image.',
            'photo.mimes'    => 'Only JPG, JPEG, PNG, and WEBP image formats are supported.',
            'photo.max'      => 'The image size cannot exceed 2MB.',
            'major_id.exists'              => 'The selected major is invalid.',
            'expected_graduation_year.gte' => 'Expected graduation year must be after or equal to the enrollment year.',
        ];
    }
}
