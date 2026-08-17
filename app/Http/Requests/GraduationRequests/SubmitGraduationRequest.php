<?php
namespace App\Http\Requests\GraduationRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating the submission of a graduation request including certificate upload.
 */
class SubmitGraduationRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'graduation_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB Max
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'graduation_certificate.required' => 'The graduation certificate document is required.',
            'graduation_certificate.file'     => 'The uploaded certificate must be a valid file.',
            'graduation_certificate.mimes'    => 'The certificate must be a PDF, JPG, JPEG, or PNG file.',
            'graduation_certificate.max'      => 'The certificate file size must not exceed 5 megabytes.',
        ];
    }
}
