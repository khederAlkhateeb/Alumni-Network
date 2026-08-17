<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the payload for updating a faculty.
 */
class UpdateFacultyRequest extends FormRequest
{
    /**
     * Determine if the authenticated user may update a faculty.
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
        if ($this->has('university_id') && is_string($this->input('university_id'))) {
            $this->merge([
                'university_id' => trim($this->input('university_id')),
            ]);
        }

        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $faculty = $this->route('faculty');
        $facultyId = $faculty?->id ?? $this->input('id');
        $universityId = $this->input('university_id', $faculty?->university_id);

        return [
            'university_id' => ['nullable', 'integer', 'min:1', 'exists:universities,id'],
            'name'          => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('faculties', 'name')
                    ->ignore($facultyId)
                    ->where(fn ($query) => $query->where('university_id', $universityId)),
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
            'university_id.integer' => 'The university ID must be an integer.',
            'university_id.min'     => 'The university ID must be at least 1.',
            'university_id.exists'  => 'The selected university does not exist.',
            'name.string'           => 'The faculty name must be a string.',
            'name.max'              => 'The faculty name may not exceed 255 characters.',
            'name.unique'           => 'The faculty name already exists in this university.',
        ];
    }
}