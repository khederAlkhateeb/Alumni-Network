<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Removes an accepted connection for either party.
 */
class DeleteConnectionAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user, Connection $connection): Connection
    {
        if ($connection->requester_id !== $user->id && $connection->receiver_id !== $user->id) {
            throw ValidationException::withMessages([
                'connection' => 'You can only remove your own connections.',
            ]);
        }

        if ($connection->status !== enConnectionStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'connection' => 'You can only remove an accepted connection.',
            ]);
        }

        $connection->delete();

        return $connection;
    }
}
