<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the payload for creating a faculty.
 */
class StoreFacultyRequest extends FormRequest
{
    /**
     * Determine if the authenticated user may create a faculty.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'university_id' => is_string($this->input('university_id')) ? trim($this->input('university_id')) : $this->input('university_id'),
            'name'          => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', 'min:1', 'exists:universities,id'],
            'name'          => [
                'required',
                'string',
                'max:255',
                Rule::unique('faculties', 'name')
                    ->where(fn ($query) => $query->where('university_id', $this->input('university_id'))),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'university_id.required' => 'The university field is required.',
            'university_id.integer'  => 'The university ID must be an integer.',
            'university_id.min'      => 'The university ID must be at least 1.',
            'university_id.exists'   => 'The selected university does not exist.',
            'name.required'          => 'The faculty name is required.',
            'name.string'            => 'The faculty name must be a string.',
            'name.max'               => 'The faculty name may not exceed 255 characters.',
            'name.unique'            => 'The faculty name already exists in this university.',
        ];
    }
}