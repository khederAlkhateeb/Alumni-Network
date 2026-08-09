<?php

namespace App\Jobs;

use App\Actions\Notification\SendNotificationAction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $type,
        public string $message,
        public ?Model $related = null
    ) {}

    public function handle(SendNotificationAction $sendNotificationAction): void
    {
        $sendNotificationAction->execute(
            $this->userId,
            $this->type,
            $this->message,
            $this->related
        );
    }
}
