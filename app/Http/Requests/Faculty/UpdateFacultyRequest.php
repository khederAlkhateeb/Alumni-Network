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
     */
    public function rules(): array
    {
        $faculty = $this->route('faculty');
        $facultyId = $faculty?->id ?? $this->input('id');
        $universityId = $this->input('university_id', $faculty?->university_id);

        return [
            'university_id' => ['nullable', 'integer', 'min:1', 'exists:universities,id'],
            'name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('faculties', 'name')
                    ->ignore($facultyId)
                    ->where(fn ($query) => $query->where('university_id', $universityId)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'university_id.integer' => 'رقم الجامعة يجب أن يكون رقمًا صحيحًا.',
            'university_id.min' => 'رقم الجامعة يجب أن يكون أكبر من أو يساوي 1.',
            'university_id.exists' => 'الجامعة المحددة غير موجودة.',
            'name.string' => 'اسم الكلية يجب أن يكون نصًا.',
            'name.max' => 'اسم الكلية لا يجب أن يتجاوز 255 حرفًا.',
            'name.unique' => 'اسم الكلية موجود مسبقًا في هذه الجامعة.',
        ];
    }
}
