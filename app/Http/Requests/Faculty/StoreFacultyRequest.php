<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'university_id' => ['required', 'integer', 'exists:universities,id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
