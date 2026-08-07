<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts a newly sent message in real time to the other
 * participant of the conversation, via Pusher.
 *
 * Unlike a plain Laravel Event (which stays server-side), this one
 * implements ShouldBroadcast — Laravel pushes it out to Pusher, which
 * relays it to any connected browser subscribed to the conversation's
 * private channel.
 */
class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message
    ) {}

    /**
     * The channel this event broadcasts on: a private channel scoped
     * to this specific conversation, so only its two participants
     * can ever subscribe to it (see routes/channels.php).
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('conversation.' . $this->message->conversation_id);
    }

    /**
     * Custom event name on the frontend (defaults to the class name
     * otherwise, which is noisier to listen for in JS).
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Trim the payload sent to the frontend to only what's needed,
     * instead of serializing the entire Message + relations.
     */
    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'content'         => $this->message->content,
            'sender_id'       => $this->message->sender_id,
            'sender_name'     => $this->message->sender->name,
            'created_at'      => $this->message->created_at->toIso8601String(),
        ];
    }
}
