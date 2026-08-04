<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/*
|--------------------------------------------------------------------------
| Event Reminder Notification
|--------------------------------------------------------------------------
|
| Sent to a registered user 24 hours before an event they are
| registered for begins. Dispatched via a queued job to avoid
| blocking the scheduler.
|
*/

class EventReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  Event  $event
     */
    public function __construct(public readonly Event $event) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reminder: ' . $this->event->title . ' starts in 24 hours')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('This is a reminder that the following event starts in 24 hours:')
            ->line($this->event->title)
            ->line('Start date: ' . $this->event->start_date->format('Y-m-d H:i'))
            ->when($this->event->location, fn($mail) => $mail->line('Location: ' . $this->event->location))
            ->when($this->event->meeting_link, fn($mail) => $mail->action('Join Event', $this->event->meeting_link))
            ->line('We look forward to seeing you there!');
    }

    /**
     * Get the array representation of the notification (for the "database" channel).
     *
     * @param  mixed  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'event_id'   => $this->event->id,
            'title'      => $this->event->title,
            'start_date' => $this->event->start_date,
            'message'    => 'Reminder: "' . $this->event->title . '" starts in 24 hours.',
        ];
    }
}
