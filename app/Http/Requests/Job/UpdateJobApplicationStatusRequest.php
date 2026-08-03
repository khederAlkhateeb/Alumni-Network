<?php

namespace App\Http\Requests\Job;

use App\Models\JobApplication;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobApplicationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:' . implode(',', JobApplication::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'The application status must be one of: ' . implode(', ', JobApplication::STATUSES) . '.',
        ];
    }
}
