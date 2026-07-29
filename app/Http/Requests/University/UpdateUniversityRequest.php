<?php

namespace App\Http\Requests\University;

use App\Models\University;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateUniversityRequest
 * 
 * Validates request data when updating an existing university resource.
 */
class UpdateUniversityRequest extends FormRequest
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
        $university = $this->route('university');
        $universityId = $university instanceof University ? $university->getKey() : $university;

        return [    
            'name'    => ['sometimes', 'string', 'max:255', 'unique:universities,name,' . $universityId],
            'country' => ['sometimes', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255', 'unique:universities,website,' . $universityId],
            'logo'    => ['sometimes', 'nullable', 'string', 'max:2048'],
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
            'name.unique'   => 'This university name is already taken.',
            'website.url'   => 'Please enter a valid URL.',
            'website.unique' => 'This website is already registered.',
        ];
    }
}
