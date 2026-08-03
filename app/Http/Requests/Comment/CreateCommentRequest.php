<?php

namespace App\Http\Requests\Comment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCommentRequest extends FormRequest
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
            'content' => ['required', 'string', 'max:255', 'min:3'],
            'parent_comment_id' => ['nullable',
                'integer',
                Rule::exists('comments', 'id')->where('post_id', $this->route('post')->id),
                ],
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The comment content is required.',
            'content.string'   => 'The comment must be a valid text.',
            'content.min'      => 'The comment must be at least :min characters long.',
            'content.max'      => 'The comment cannot exceed :max characters.',
            'parent_comment_id.exists'   => 'The selected parent comment does not exist.',
        ];
    }
}
