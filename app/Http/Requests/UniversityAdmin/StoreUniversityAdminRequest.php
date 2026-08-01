<?php

namespace App\Http\Requests\UniversityAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\UniversityAdmin;
use Illuminate\Validation\Rule;
use App\Models\User;

class StoreUniversityAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create',UniversityAdmin::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'university_id' => [
                'required',
                'integer',
                'exists:universities,id',
                Rule::unique('university_admins', 'university_id'),
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'university_id.unique' => 'this university already has an admin assigned.',
            'email.unique' => 'this email is already in use.',
            'university_id.exists' => 'the selected university does not exist.',
            'university_id.required' => 'the university field is required.',
            'name.required' => 'the name field is required.',
            'email.required' => 'the email field is required.',
            'password.required' => 'the password field is required.',
            'password.min' => 'the password must be at least 8 characters.',
        ];
    }
}
