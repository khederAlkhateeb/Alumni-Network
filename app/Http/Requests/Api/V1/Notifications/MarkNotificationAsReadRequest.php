<?php

namespace App\Http\Requests\Api\V1\Notifications;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Mark Notification As Read Request
|--------------------------------------------------------------------------
|
| Validates the {notification} route parameter for
| PATCH /notifications/{notification}/read.
|
| Deliberately format-agnostic: the identifier is validated as a
| non-empty string rather than a strict 'uuid' rule, since its exact
| format is an implementation detail of the Notification model
| (auto-increment id, UUID, or ULID). Existence AND ownership are
| the real source of truth and are enforced downstream by
| MarkNotificationAsReadAction via
| $user->notifications()->findOrFail($id) — per Rule 5.1
| (User Scoping) — which returns a 404 for both "not found" and
| "belongs to another user".
|
*/

class MarkNotificationAsReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Any authenticated user may attempt to mark a notification as
     * read; ownership is enforced by the scoped query in the Action,
     * not here, to avoid an extra unscoped lookup at the Request layer.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notification' => ['required', 'string'],
        ];
    }

    /**
     * Merge the route-bound {notification} parameter into the
     * validated payload, since it arrives via the URL, not the body.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notification' => $this->route('notification'),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notification.required' => 'Notification identifier is missing from the request.',
            'notification.string'   => 'Notification identifier is invalid.',
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
            'notification' => 'notification id',
        ];
    }
}
