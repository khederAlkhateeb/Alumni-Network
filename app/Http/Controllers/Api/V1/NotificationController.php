<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use App\V1\Actions\Notifications\GetUserNotificationsAction;
use App\V1\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\V1\Actions\Notifications\MarkNotificationAsReadAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
| The controller stays thin on purpose: business logic lives in the
| Actions (each exposing a single handle() method), and this class
| is only responsible for wiring the Request to the right Action
| and translating the outcome into the unified success/error
| response shape via try/catch. None of these three endpoints take
| meaningful input worth a dedicated Form Request, so a plain
| Request is used throughout.
|
*/

class NotificationController extends Controller
{
    /**
     * Inject action classes used to handle notification workflows.
     *
     * @param  GetUserNotificationsAction  $getUserNotifications
     * @param  MarkNotificationAsReadAction  $markNotificationAsRead
     * @param  MarkAllNotificationsAsReadAction  $markAllNotificationsAsRead
     */
    public function __construct(
        private readonly GetUserNotificationsAction $getUserNotifications,
        private readonly MarkNotificationAsReadAction $markNotificationAsRead,
        private readonly MarkAllNotificationsAsReadAction $markAllNotificationsAsRead,
    ) {}

    /**
     * GET /notifications
     *
     * List the authenticated user's notifications, newest first.
     * Rule 5.7: Pagination (20 items per page).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $notifications = $this->getUserNotifications->handle($request->user());

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
     * @param  Request  $request
     * @param  string  $notification
     * @return JsonResponse
     */
    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        try {
            $model = $this->markNotificationAsRead->handle($request->user(), $notification);

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
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $count = $this->markAllNotificationsAsRead->handle($request->user());

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
