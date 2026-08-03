<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;

class ApplyForJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['nullable', 'string'],
            'resume' => ['nullable', 'string', 'max:255'],
        ];
    }
}
