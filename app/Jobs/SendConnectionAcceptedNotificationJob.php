<?php

namespace App\Jobs;

use App\Models\Connection;
use App\Models\Notification;
use App\Notifications\ConnectionAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class SendConnectionAcceptedNotificationJob implements ShouldQueue
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
                'type' => 'connection_accepted',
                'related_id' => $connection->id,
                'related_type' => Connection::class,
                'message' => "{$receiverName} accepted your connection request.",
                'read_at' => null,
            ]);
        });

        if ($requester?->email) {
            NotificationFacade::send($requester, new ConnectionAcceptedNotification($connection, $receiverName));
        }
    }
}
