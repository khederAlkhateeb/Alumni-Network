<?php

namespace App\Http\Requests\Post;

use App\Enums\PostVisibility;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePostRequest extends FormRequest
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
            'content' => ['required', 'string', 'max:2000', 'min:15'],
            'visibility' => ['required', Rule::enum(PostVisibility::class)],
            'image'    => ['nullable','file','image', 'mimes:jpg,jpeg,png,webp' , 'max:5242880'],

        ];
    }
    public function messages(): array
    {
        return [
            'content.required'    => 'Post content is required.',
            'content.string'      => 'Post content must be a valid string.',
            'content.min'         => 'Post content must be at least :min characters.',
            'content.max'         => 'Post content must not exceed :max characters.',
            'visibility.required' => 'Visibility level must be specified.',
            'visibility.enum'     => 'Invalid visibility value. Allowed: public, connections, university.',
            'image.file'          => 'The attachment must be a valid file.',
            'image.image'         => 'The attachment must be an image.',
            'image.mimes'         => 'Image must be one of the following types: jpg, jpeg, png, webp.',
            'image.max'           => 'Image size must not exceed 5MB.',
        ];
    }
}
