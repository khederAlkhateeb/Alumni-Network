<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Jobs\SendConnectionRejectedNotificationJob;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Rejects a pending connection request (receiver only).
 */
class RejectConnectionAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, Connection $connection): Connection
    {
        if ($connection->receiver_id !== $user->id) {
            throw ValidationException::withMessages([
                'connection' => 'You can only reject a connection request sent to you.',
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
            'status' => enConnectionStatus::REJECTED,
            'rejected_at' => Carbon::now(),
            'accepted_at' => null,
        ]);

        $connection = $connection->fresh();

        SendConnectionRejectedNotificationJob::dispatch($connection);

        return $connection;
    }
}
