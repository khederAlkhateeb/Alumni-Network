<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Block a connection between the users.
 */
class BlockConnectionAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, Connection $connection): Connection
    {
        if ($user->id === $connection->receiver_id ) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You cannot block yourself.',
            ]);
        }

        if ($connection->status !== enConnectionStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'connection' => 'You can only remove an accepted connection.',
            ]);
        }

        if (Connection::query()->blockedBetween($user, $connection->receiver)->exists()) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You already blocked this user.',
            ]);
        }
        $connection->update([
            'status' => enConnectionStatus::BLOCKED,
        ]);

        return $connection->fresh();
    }
}
