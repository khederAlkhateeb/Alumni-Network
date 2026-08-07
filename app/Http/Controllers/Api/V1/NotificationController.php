<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
*/

class NotificationController extends Controller
{
    use ApiResponseTrait;

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
        $query = $request->user()->notifications();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        $notifications = $query->latest()->paginate(20);

        return $this->paginated(
            NotificationResource::collection($notifications),
            'Notifications retrieved successfully.'
        );
    }

    /**
     * PATCH /notifications/{notification}/read
     *
     * Mark a single notification as read. Scoped to the authenticated
     * user, so attempting to mark another user's notification returns 404.
     *
     * @param  Request  $request
     * @param  int  $notification
     * @return JsonResponse
     */
    public function markAsRead(Request $request, int $notification): JsonResponse
    {
        $model = $request->user()->notifications()->findOrFail($notification);

        $model->markAsRead();

        return $this->success(
            new NotificationResource($model),
            'Notification marked as read.'
        );
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
        $unreadQuery = $request->user()->unreadNotifications();

        $count = $unreadQuery->count();

        $unreadQuery->update(['read_at' => now()]);

        return $this->success(
            ['marked_count' => $count],
            'All notifications marked as read.'
        );
    }
}
