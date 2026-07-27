<?php

namespace App\Http\Requests\University;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit-university');
    }

    public function rules(): array
    {
        $universityId = $this->route('university');

        return [
            'name'    => ['sometimes', 'string', 'max:255', 'unique:universities,name,' . $universityId],
            'country' => ['sometimes', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255', 'unique:universities,website,' . $universityId],
            'logo'    => ['sometimes', 'nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique'   => 'This university name is already taken.',
            'website.url'   => 'Please enter a valid URL.',
            'website.unique' => 'This website is already registered.',
        ];
    }
}
