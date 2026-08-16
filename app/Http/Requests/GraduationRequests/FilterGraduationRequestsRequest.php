<?php
namespace App\Http\Requests\GraduationRequests;

use App\Enums\GraduationRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Form request for validating graduation requests filtering parameters.
 */
class FilterGraduationRequestsRequest extends FormRequest
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
            'status'   => ['nullable', new Enum(GraduationRequestStatus::class)],
            'major_id' => ['nullable', 'integer', 'exists:majors,id'],
            'search'   => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
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
            'status.in'        => 'Selected status must be one of: pending, approved, or rejected.',
            'major_id.exists'  => 'The selected major does not exist in our records.',
            'search.max'       => 'Search term must not exceed 255 characters.',
            'per_page.integer' => 'Pagination per-page limit must be a number.',
        ];
    }
}
