<?php
namespace App\Http\Requests\Connection;

use Illuminate\Foundation\Http\FormRequest;

class FilterConnectionByNameRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

      /**
     * Prepare inputs for validation (Sanitizing input).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->name)) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'min:1', 'max:255'],
        ];
    }



    /**
     * Custom English error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'The search query must be a valid string.',
            'name.min'    => 'The search query must be at least 1 character.',
            'name.max'    => 'The search query cannot exceed 255 characters.',
        ];
    }
}
