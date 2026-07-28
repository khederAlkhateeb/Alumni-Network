<?php

namespace App\Http\Requests\University;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:universities,name'],
            'country' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255', 'unique:universities,website'],
            'logo' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'University name is required.',
            'name.unique' => 'This university name is already taken.',
            'country.required' => 'Country is required.',
            'website.url' => 'Please enter a valid URL.',
            'website.unique' => 'This website is already registered.',
        ];
    }
}
