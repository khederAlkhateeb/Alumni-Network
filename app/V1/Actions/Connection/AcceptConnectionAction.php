<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Jobs\SendConnectionAcceptedNotificationJob;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Accepts a pending connection request (receiver only).
 */
class AcceptConnectionAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, Connection $connection): Connection
    {
        if ($connection->receiver_id !== $user->id) {
            throw ValidationException::withMessages([
                'connection' => 'You can only accept a connection request sent to you.',
            ]);
        }

        if (Connection::query()->blockedBetween($connection->requester_id, $connection->receiver_id)->exists()) {
            throw ValidationException::withMessages([
                'connection' => 'You cannot accept a connection request from a blocked user.',
            ]);
        }

        if ($connection->status === enConnectionStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'connection' => 'You are already connected with this user.',
            ]);
        }

        if ($connection->status !== enConnectionStatus::PENDING) {
            throw ValidationException::withMessages([
                'connection' => 'This connection request is no longer pending.',
            ]);
        }

        $connection->update([
            'status' => enConnectionStatus::ACCEPTED,
            'accepted_at' => Carbon::now(),
            'rejected_at' => null,
        ]);

        $connection = $connection->fresh();

        SendConnectionAcceptedNotificationJob::dispatch($connection);

        return $connection;
    }
}
