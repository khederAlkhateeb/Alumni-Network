<?php

namespace App\Http\Requests\Api\V1\Notifications;

use Illuminate\Foundation\Http\FormRequest;

/*
|--------------------------------------------------------------------------
| Mark All Notifications As Read Request
|--------------------------------------------------------------------------
|
| POST /notifications/read-all takes no input today. This class
| exists so the controller signature stays consistent with the
| other two endpoints, and as a ready extension point should the
| endpoint later accept e.g. a 'type' filter to bulk-read only a
| specific notification type.
|
*/

class MarkAllNotificationsAsReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Any authenticated user may mark their own notifications as read.
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
        return [];
    }
}
