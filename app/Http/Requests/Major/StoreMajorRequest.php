<?php

namespace App\Http\Requests\Major;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMajorRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $faculty = $this->route('faculty');
        $facultyId = is_object($faculty) ? $faculty->id : $faculty;
        
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('majors', 'name')->where('faculty_id', $facultyId),
            ],
        ];
    }
}
