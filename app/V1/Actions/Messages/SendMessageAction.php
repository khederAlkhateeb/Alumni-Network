<?php

namespace App\V1\Actions\Messages;

use App\Enums\enConnectionStatus;
use App\Enums\MentorshipRequestStatus;
use App\Events\MessageSent;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\MentorshipRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SendMessageAction
{
    /**
     * Send a message from $sender to $receiverId, creating the
     * conversation between them if it doesn't already exist.
     *
     * Authorization: the two users must either have an accepted
     * Connection, or an accepted Mentorship relationship between
     * them (spec 3.12's stated exception) — otherwise messaging
     * is not allowed.
     *
     * @param User   $sender     The authenticated user sending the message.
     * @param int    $receiverId The recipient's user ID.
     * @param string $content    The message text.
     *
     * @return Message The created message, with 'sender' loaded.
     *
     * @throws ValidationException If the two users aren't allowed to message each other.
     */
    public function handle(User $sender, int $receiverId, string $content): Message
    {
        if (!$this->canMessage($sender->id, $receiverId)) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You can only message accepted connections or mentorship partners.',
            ]);
        }

        // Always store the smaller ID as user_one_id, so the same pair
        // never gets stored twice in reversed column order.
        $userOneId = min($sender->id, $receiverId);
        $userTwoId = max($sender->id, $receiverId);

        $message = DB::transaction(function () use ($sender, $userOneId, $userTwoId, $content) {
            $conversation = Conversation::firstOrCreate([
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]);

            return Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
                'content'         => $content,
            ]);
        });

        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    /**
     * Whether $userIdA and $userIdB are allowed to message each other:
     * either an accepted Connection, or an accepted Mentorship request
     * (in either mentor/mentee direction), per spec 3.12.
     */
    private function canMessage(int $userIdA, int $userIdB): bool
    {
        $hasAcceptedConnection = Connection::query()
            ->where('status', enConnectionStatus::ACCEPTED->value)
            ->where(function ($query) use ($userIdA, $userIdB) {
                $query->where(fn ($q) => $q->where('requester_id', $userIdA)->where('receiver_id', $userIdB))
                    ->orWhere(fn ($q) => $q->where('requester_id', $userIdB)->where('receiver_id', $userIdA));
            })
            ->exists();

        if ($hasAcceptedConnection) {
            return true;
        }

        return MentorshipRequest::query()
            ->where('status', MentorshipRequestStatus::ACCEPTED->value)
            ->where(function ($query) use ($userIdA, $userIdB) {
                $query->where(fn ($q) => $q->where('mentor_id', $userIdA)->where('mentee_id', $userIdB))
                    ->orWhere(fn ($q) => $q->where('mentor_id', $userIdB)->where('mentee_id', $userIdA));
            })
            ->exists();
    }
}
