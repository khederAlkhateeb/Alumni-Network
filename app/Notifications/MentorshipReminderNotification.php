<?php

namespace App\Notifications;

use App\Models\MentorshipRequest;
use App\Models\Notification as CustomNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Class MentorshipReminderNotification
 *
 * Sends a 24-hour reminder notification to both mentors and mentees
 * regarding an upcoming scheduled mentorship session.
 *
 * @package App\Notifications
 */
class MentorshipReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  MentorshipRequest  $mentorshipRequest
     * @param  string  $userRole  Role of the recipient ('mentor' or 'mentee')
     */
    public function __construct(
        public MentorshipRequest $mentorshipRequest,
        public string $userRole
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  object  $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [ 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  object  $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $otherParty = $this->userRole === 'mentor'
            ? $this->mentorshipRequest->mentee->name
            : $this->mentorshipRequest->mentor->name;

        return (new MailMessage)
            ->subject('Reminder: Upcoming Mentorship Session in 24 Hours')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('This is a friendly reminder that your mentorship session for program "' . $this->mentorshipRequest->program->title . '" with ' . $otherParty . ' is coming up in 24 hours.')
            ->action('View Request Details', url('/mentorship-requests/' . $this->mentorshipRequest->id))
            ->line('Please ensure you are prepared and on time.');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @param  object  $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $otherParty = $this->userRole === 'mentor'
            ? $this->mentorshipRequest->mentee->name
            : $this->mentorshipRequest->mentor->name;

        return [
            'mentorship_request_id' => $this->mentorshipRequest->id,
            'program_id'            => $this->mentorshipRequest->program_id,
            'program_title'         => $this->mentorshipRequest->program->title,
            'with_user'             => $otherParty,
            'scheduled_at'          => $this->mentorshipRequest->scheduled_at,
            'message'               => "Reminder: You have an upcoming mentorship session with {$otherParty} in 24 hours.",
        ];
    }

    /**
     * Store the notification in the custom database table.
     *
     * @param  object  $notifiable
     * @return array<string, mixed>
     */

}
