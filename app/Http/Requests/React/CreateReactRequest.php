<?php

namespace App\Http\Requests\React;

use App\Enums\ReactionType;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReactRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ReactionType::class)],
            'reactable_type' => ['required', Rule::in(['post', 'comment'])],
            'reactable_id' => ['required',
                'integer',
                function ($attribute, $value, $fail)
                {
                    $modelClass = match($this->input('reactable_type')) {
                        'post' => Post::class,
                        'comment' => Comment::class,
                        default  => Post::class
                    };
                    if (!$modelClass || !$modelClass::whereKey($value)->exists()) {
                        $fail('The selected reactable_id is invalid.');
                    }
                },
            ],
        ];
    }
    /**
     * Resolve 'reactable_type' from its short form ('post'/'comment')
     * to the actual model class name, for use inside the Action.
     */
    public function resolveReactableClass(): string
    {
        return match ($this->input('reactable_type')) {
            'post'    => Post::class,
            'comment' => Comment::class,
        };
    }
}
