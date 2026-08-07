<?php

namespace App\Actions\Notification;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;

class SendNotificationAction
{
    /**
     * Send a notification to a specific user.
     */
    public function execute(
        int $userId,
        string $type,
        string $message,
        ?Model $related = null
    ): Notification {
        return Notification::create([
            'user_id'      => $userId,
            'type'         => $type,
            'message'      => $message,
            'related_type' => $related ? $related->getMorphClass() : null,
            'related_id'   => $related ? $related->getKey() : null,
        ]);
    }
}
