<?php

namespace App\Http\Requests\MentorshipRequest;

use App\Rules\MatchMentorCollege;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMentorshipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Inject the currently authenticated user ID as the mentee_id
        $this->merge([
            'mentee_id' => $this->user()?->id,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Validate active program existence
            'program_id' => [
                'required',
                'integer',
                Rule::exists('mentorship_programs', 'id')->where(function ($query) {
                    $query->where('status', 'active')
                          ->whereDate('start_date', '<=', now())
                          ->whereDate('end_date', '>=', now());
                }),
            ],

            // Validate mentor ID & prevent duplicate requests or self-requesting
            'mentor_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:mentee_id',
                new MatchMentorCollege(),
                // Prevent duplicate requests for the same program and mentee
                Rule::unique('mentorship_requests')->where(function ($query) {
                    return $query->where('program_id', $this->program_id)
                                 ->where('mentee_id', $this->user()?->id);
                }),
            ],

            // Optional introduction message
            'intro_message' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Custom validation error messages in English.
     */
    public function messages(): array
    {
        return [
            'program_id.required' => 'Please select a mentorship program.',
            'program_id.exists'   => 'The selected mentorship program does not exist or is currently inactive.',

            'mentor_id.required'  => 'Please select a mentor.',
            'mentor_id.exists'    => 'The selected mentor does not exist.',
            'mentor_id.different' => 'You cannot send a mentorship request to yourself.',
            'mentor_id.unique'    => 'You have already sent a mentorship request to this mentor for this program.',

            'intro_message.max'   => 'The introduction message must not exceed 1000 characters.',
        ];
    }
}
