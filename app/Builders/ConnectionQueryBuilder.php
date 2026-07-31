<?php

namespace App\Builders;

use App\Enums\enConnectionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Custom Eloquent builder for Connection query constraints.
 *
 * Actions orchestrate business rules; this builder owns reusable
 * query conditions only.
 */
class ConnectionQueryBuilder extends Builder
{
    /**
     * Limit to rows involving the given user as requester or receiver.
     */
    public function forUser(User|int $user): static
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $this->where(function (self $query) use ($userId) {
            $query->where('requester_id', $userId)
                ->orWhere('receiver_id', $userId);
        });
    }

    /**
     * Limit to the connection row between two users in either direction.
     */
    public function betweenUsers(User|int $a, User|int $b): static
    {
        $aId = $a instanceof User ? $a->id : $a;
        $bId = $b instanceof User ? $b->id : $b;

        return $this->where(function (self $query) use ($aId, $bId) {
            $query->where(function (self $inner) use ($aId, $bId) {
                $inner->where('requester_id', $aId)->where('receiver_id', $bId);
            })->orWhere(function (self $inner) use ($aId, $bId) {
                $inner->where('requester_id', $bId)->where('receiver_id', $aId);
            });
        });
    }

    /**
     * get a list of accepted connections
     */
    public function acceptedConnections(): static
    {
        return $this->where('status', enConnectionStatus::ACCEPTED);
    }


    /**
     * Filter by one or more connection statuses.
     */
    public function filterByStatus(array|string|enConnectionStatus $status): static
    {
        $statuses = array_map(
            fn ($value) => $value instanceof enConnectionStatus ? $value->value : $value,
            (array) $status
        );

        return $this->when(
            $statuses !== [],
            fn (self $query) => $query->whereIn('status', $statuses)
        );
    }

    /**
     * Connections marked blocked between the two users (either direction).
     */
    public function blockedBetween(User|int $a, User|int $b): static
    {
        return $this->betweenUsers($a, $b)
            ->where('status', enConnectionStatus::BLOCKED);
    }

    /**
     * Rejected requests from requester to receiver within the cooldown window.
     */
    public function recentlyRejectedBy(User|int $receiver, User|int $requester, ?Carbon $cutoff = null): static
    {
        $receiverId = $receiver instanceof User ? $receiver->id : $receiver;
        $requesterId = $requester instanceof User ? $requester->id : $requester;
        $cutoff ??= Carbon::now()->subDays((int) config('connections.rejection_cooldown_days', 30));

        return $this->where('requester_id', $requesterId)
            ->where('receiver_id', $receiverId)
            ->where('status', enConnectionStatus::REJECTED)
            ->where('rejected_at', '>=', $cutoff);
    }
}
