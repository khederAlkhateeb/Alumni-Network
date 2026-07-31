<?php

namespace App\Jobs;

use App\Models\Connection;
use App\Models\Notification;
use App\Notifications\ConnectionRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendConnectionRejectedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public Connection $connectionModel)
    {
    }

    /**
     * execute the job
     * @return void
     */
    public function handle(): void
    {
        $connection = $this->connectionModel->loadMissing(['sender', 'receiver']);
        $receiverName = $connection->receiver?->name ?? 'A user';
        $requester = $connection->requester;

        DB::transaction(function () use ($connection, $receiverName) {
            Notification::create([
                'user_id' => $connection->requester_id,
                'type' => 'connection_rejected',
                'related_id' => $connection->id,
                'related_type' => Connection::class,
                'message' => "{$receiverName} rejected your connection request.",
                'read_at' => null,
            ]);
        });

        if ($requester?->email) {
            NotificationFacade::send($requester, new ConnectionRejectedNotification($connection, $receiverName));
        }
    }
}
