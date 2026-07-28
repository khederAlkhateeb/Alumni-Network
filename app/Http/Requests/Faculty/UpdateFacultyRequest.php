<?php

namespace App\Http\Requests\Faculty;

use Illuminate\Foundation\Http\FormRequest;

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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'university_id' => ['nullable', 'integer', 'exists:universities,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
