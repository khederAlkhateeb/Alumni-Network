<?php

namespace App\Http\Requests\UniversityAdmin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\UniversityAdmin;
use App\Models\User;

class UpdateUniversityAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $admin = $this->route('university_admin') ?? $this->route('universityAdmin');
        return $this->user()->can('update', $admin);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $admin = $this->route('university_admin') ?? $this->route('universityAdmin');
        $userId = $admin ? $admin->user_id : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 
                'string', 
                'email', 
                'max:255', 
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
