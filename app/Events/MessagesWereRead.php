<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class MessagesWereRead
 *
 * Represents an event broadcasted when messages in a conversation
 * are marked as read by a user, notifying the original sender.
 */
class MessagesWereRead implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $conversationId;
    public $readerId;
    public $receiverId;

    /**
     * Create a new event instance.
     *
     * @param int $conversationId The ID of the conversation where messages were read.
     * @param int $readerId The ID of the user who read the messages (auth user).
     * @param int $receiverId The ID of the user who originally sent the messages (notified user).
     */
    public function __construct($conversationId, $readerId, $receiverId)
    {
        $this->conversationId = $conversationId;
        $this->readerId = $readerId;
        $this->receiverId = $receiverId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->receiverId);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'messages.read';
    }
}
