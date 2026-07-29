<?php

namespace App\Http\Requests\University;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreUniversityRequest
 * 
 * Validates request data when creating a new university resource.
 */
class StoreUniversityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:universities,name'],
            'country' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255', 'unique:universities,website'],
            'logo' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * Get the custom validation error messages.
     * 
     * @return array<string, string>
     */
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
