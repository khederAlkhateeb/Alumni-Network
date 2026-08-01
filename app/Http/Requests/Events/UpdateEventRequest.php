<?php

namespace App\Http\Requests\Api\V1\Events;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Update Event Request
|--------------------------------------------------------------------------
|
| Validates the payload used to update an existing event. All fields
| are optional ("sometimes") so partial updates are supported.
| "location" and "meeting_link" requirements still depend on "type"
| whenever "type" is present in the request payload.
| Authorization is delegated to the EventPolicy (only the University
| Admin who owns the event's university may manage/update it).
|
*/

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage', $this->route('event'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:5000'],
            'type'         => ['sometimes', 'required', 'in:campus,online,hybrid'],
            'location'     => ['required_if:type,campus,hybrid', 'nullable', 'string', 'max:255'],
            'meeting_link' => ['required_if:type,online,hybrid', 'nullable', 'url', 'max:255'],
            'start_date'   => ['sometimes', 'required', 'date'],
            'end_date'     => ['sometimes', 'required', 'date', 'after:start_date'],
            'capacity'     => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status'       => ['sometimes', 'in:upcoming,ongoing,completed,cancelled'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required'            => 'The event title cannot be empty.',
            'title.max'                 => 'The event title may not be longer than 255 characters.',
            'description.max'           => 'The event description may not be longer than 5000 characters.',
            'type.required'             => 'The event type cannot be empty.',
            'type.in'                   => 'The event type must be one of: on_campus, online, or hybrid.',
            'location.required_if'      => 'The location is required for on-campus or hybrid events.',
            'meeting_link.required_if'  => 'The meeting link is required for online or hybrid events.',
            'meeting_link.url'          => 'The meeting link must be a valid URL.',
            'start_date.required'       => 'The event start date cannot be empty.',
            'start_date.date'           => 'The event start date must be a valid date.',
            'end_date.required'         => 'The event end date cannot be empty.',
            'end_date.date'             => 'The event end date must be a valid date.',
            'end_date.after'            => 'The event end date must be after the start date.',
            'capacity.integer'          => 'The event capacity must be a whole number.',
            'capacity.min'              => 'The event capacity must be at least 1.',
            'status.in'                 => 'The event status must be one of: upcoming, ongoing, completed, or cancelled.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_date'   => 'start date',
            'end_date'     => 'end date',
            'meeting_link' => 'meeting link',
        ];
    }
}
