<?php

namespace App\Http\Requests\Api\V1\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Store Event Request
|--------------------------------------------------------------------------
|
| Validates the payload used to create a new event for a university.
| "location" is required for on-campus/hybrid events, while
| "meeting_link" is required for online/hybrid events.
| Authorization is delegated to the EventPolicy (only a University
| Admin belonging to the target university may create events).
|
*/

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Event::class, $this->route('university')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:5000'],
            'type'         => ['required', 'in:campus,online,hybrid'],
            'location'     => ['required_if:type,campus,hybrid', 'nullable', 'string', 'max:255'],
            'meeting_link' => ['required_if:type,online,hybrid', 'nullable', 'url', 'max:255'],
            'start_date'   => ['required', 'date', 'after_or_equal:today'],
            'end_date'     => ['required', 'date', 'after:start_date'],
            'capacity'     => ['nullable', 'integer', 'min:1'],
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
            'title.required'            => 'The event title is required.',
            'title.max'                 => 'The event title may not be longer than 255 characters.',
            'description.max'           => 'The event description may not be longer than 5000 characters.',
            'type.required'             => 'The event type is required.',
            'type.in'                   => 'The event type must be one of: campus, online, or hybrid.',
            'location.required_if'      => 'The location is required for campus or hybrid events.',
            'meeting_link.required_if'  => 'The meeting link is required for online or hybrid events.',
            'meeting_link.url'          => 'The meeting link must be a valid URL.',
            'start_date.required'       => 'The event start date is required.',
            'start_date.date'           => 'The event start date must be a valid date.',
            'start_date.after_or_equal' => 'The event start date cannot be in the past.',
            'end_date.required'         => 'The event end date is required.',
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
