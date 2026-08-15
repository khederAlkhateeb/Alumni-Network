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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'university_id' => is_string($this->input('university_id')) ? trim($this->input('university_id')) : $this->input('university_id'),
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', 'min:1', 'exists:universities,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('faculties', 'name')
                    ->where(fn ($query) => $query->where('university_id', $this->input('university_id'))),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'university_id.required' => 'حقل الجامعة مطلوب.',
            'university_id.integer' => 'رقم الجامعة يجب أن يكون رقمًا صحيحًا.',
            'university_id.min' => 'رقم الجامعة يجب أن يكون أكبر من أو يساوي 1.',
            'university_id.exists' => 'الجامعة المحددة غير موجودة.',
            'name.required' => 'اسم الكلية مطلوب.',
            'name.string' => 'اسم الكلية يجب أن يكون نصًا.',
            'name.max' => 'اسم الكلية لا يجب أن يتجاوز 255 حرفًا.',
            'name.unique' => 'اسم الكلية موجود مسبقًا في هذه الجامعة.',
        ];
    }
}
