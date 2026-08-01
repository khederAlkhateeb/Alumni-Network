<?php

namespace App\Notifications;

use App\Mail\ConnectionAccepted;
use App\Models\Connection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConnectionAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Connection $connection,
        public readonly string $receiverName,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): ConnectionAccepted
    {
        return (new ConnectionAccepted($this->connection, $this->receiverName))
            ->to($notifiable->email);
    }
}
