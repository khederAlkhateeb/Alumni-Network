<?php

namespace App\Http\Requests\Job;

use App\Models\JobListing;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:' . implode(',', JobListing::TYPES)],
            'description' => ['nullable', 'string'],
            'requirements' => ['nullable', 'string'],
            'salary_range' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:today'],
            'status' => ['nullable', 'string', 'in:' . JobListing::STATUS_ACTIVE . ',' . JobListing::STATUS_CLOSED . ',' . JobListing::STATUS_EXPIRED],
            'university_id' => ['sometimes', 'nullable', 'integer', 'exists:universities,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The job type must be one of: ' . implode(', ', JobListing::TYPES) . '.',
            'status.in' => 'The status must be active, closed, or expired.',
            'expires_at.after' => 'The expiration date must be a future date.',
        ];
    }
}
