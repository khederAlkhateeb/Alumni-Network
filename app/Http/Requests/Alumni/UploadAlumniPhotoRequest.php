<?php

namespace App\Http\Requests\Alumni;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadAlumniPhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048', // Max size in kilobytes (2MB)
            ],
        ];
    }

    /**
     * Custom error messages for image upload validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Please select an image file to upload.',
            'photo.file'     => 'The uploaded item must be a valid file.',
            'photo.image'    => 'The uploaded file must be an image.',
            'photo.mimes'    => 'Only JPG, JPEG, PNG, and WEBP image formats are supported.',
            'photo.max'      => 'The image size cannot exceed 2MB.',
        ];
    }
    
}
