<?php

namespace App\Http\Controllers\Api\V1;

use App\V1\Actions\Notifications\GetUserNotificationsAction;
use App\V1\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\V1\Actions\Notifications\MarkNotificationAsReadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\IndexNotificationRequest;
use App\Http\Requests\Api\V1\Notifications\MarkAllNotificationsAsReadRequest;
use App\Http\Requests\Api\V1\Notifications\MarkNotificationAsReadRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Throwable;

/*
| Notification Controller (API v1)
|--------------------------------------------------------------------------
|
| Manages the authenticated user's own database notifications:
| listing, marking a single notification as read, and marking all
| unread notifications as read. Every query is scoped through
| $request->user()->notifications(), so a user can never read or
| modify another user's notifications.
|
| The controller stays thin on purpose: validation lives in the
| Form Requests, business logic lives in the Actions, and this
| class is only responsible for wiring the two together and
| translating the outcome into the unified success/error response
| shape via try/catch.
|
*/

class NotificationController extends Controller
{
    /**
     * GET /notifications
     *
     * List the authenticated user's notifications, newest first.
     * Rule 5.7: Pagination (20 items per page).
     *
     * @param  IndexNotificationRequest  $request
     * @param  GetUserNotificationsAction  $action
     * @return JsonResponse
     */
    public function index(IndexNotificationRequest $request, GetUserNotificationsAction $action): JsonResponse
    {
        try {
            $notifications = $action($request->user());

            return $this->successResponse(
                data: NotificationResource::collection($notifications)->resolve($request),
                message: 'Notifications retrieved successfully.',
                meta: [
                    'current_page' => $notifications->currentPage(),
                    'last_page'    => $notifications->lastPage(),
                    'per_page'     => $notifications->perPage(),
                    'total'        => $notifications->total(),
                ],
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Failed to retrieve notifications.', code: 500);
        }
    }

    /**
     * PATCH /notifications/{notification}/read
     *
     * Mark a single notification as read. Scoped to the authenticated
     * user, so attempting to mark another user's notification returns 404.
     *
     * @param  MarkNotificationAsReadRequest  $request
     * @param  string  $notification
     * @param  MarkNotificationAsReadAction  $action
     * @return JsonResponse
     */
    public function markAsRead(MarkNotificationAsReadRequest $request, string $notification, MarkNotificationAsReadAction $action): JsonResponse
    {
        try {
            $model = $action($request->user(), $notification);

            return $this->successResponse(
                data: new NotificationResource($model),
                message: 'Notification marked as read.',
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Notification not found.', code: 404);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Failed to mark notification as read.', code: 500);
        }
    }

    /**
     * POST /notifications/read-all
     *
     * Mark all of the authenticated user's unread notifications as read.
     *
     * @param  MarkAllNotificationsAsReadRequest  $request
     * @param  MarkAllNotificationsAsReadAction  $action
     * @return JsonResponse
     */
    public function markAllAsRead(MarkAllNotificationsAsReadRequest $request, MarkAllNotificationsAsReadAction $action): JsonResponse
    {
        try {
            $count = $action($request->user());

            return $this->successResponse(
                data: ['marked_count' => $count],
                message: 'All notifications marked as read.',
            );
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Failed to mark notifications as read.', code: 500);
        }
    }
}
