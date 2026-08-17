<?php
namespace App\Http\Requests\GraduationRequests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating the rejection reason when rejecting a graduation request.
 */
class RejectGraduationRequestRequest extends FormRequest
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
            'rejection_reason' => ['required', 'string', 'min:5', 'max:1000'],
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
            'rejection_reason.required' => 'A rejection reason is required.',
            'rejection_reason.string'   => 'The rejection reason must be a text string.',
            'rejection_reason.min'      => 'The rejection reason must be at least 5 characters long.',
            'rejection_reason.max'      => 'The rejection reason may not exceed 1000 characters.',
        ];
    }
}
