<?php

namespace App\Policies;

use App\Models\Connection;
use App\Models\User;

class ConnectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-connections');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('send-connection-request');
    }

    /**
     * Determine whether the user can accept a connection request.
     */
    public function accept(User $user, Connection $connection): bool
    {
        return $user->can('accept-connection-request');
    }

    /**
     * Determine whether the user can reject a connection request.
     */
    public function reject(User $user, Connection $connection): bool
    {
        return $user->can('reject-connection-request');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Connection $connection): bool
    {
        if (! $user->can('remove-connection')) {
            return false;
        }

        return $connection->receiver_id === $user->id || $connection->requester_id === $user->id;
    }

    /**
     * check if the relation between
     */
    public function block(User $user, Connection $connection): bool
    {
        if (! $user->can('block-user')) {
            return false;
        }

        return $connection->receiver_id === $user->id || $connection->requester_id === $user->id;
    }
}
