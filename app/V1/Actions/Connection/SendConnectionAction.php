<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Sends a connection request while enforcing connection business rules.
 */
class SendConnectionAction
{
    /**
     * @throws ValidationException
     */
    public function handle(User $requester, User $receiver): Connection
    {
        if ($requester->is($receiver)) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You cannot send a connection request to yourself.',
            ]);
        }

        if (Connection::query()->blockedBetween($requester, $receiver)->exists()) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You cannot send a connection request to this user.',
            ]);
        }

        if (Connection::query()->recentlyRejectedBy($receiver, $requester)->exists()) {
            throw ValidationException::withMessages([
                'receiver_id' => 'This user recently rejected your connection request. Please try again later.',
            ]);
        }

        $existing = Connection::query()->betweenUsers($requester, $receiver)->first();

        if ($existing && $existing->status === enConnectionStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You are already connected with this user.',
            ]);
        }

        if ($existing && $existing->status === enConnectionStatus::PENGING) {
            throw ValidationException::withMessages([
                'receiver_id' => 'A connection request has already been sent to this user.',
            ]);
        }

        if ($existing) {
            $existing->forceFill([
                'status' => enConnectionStatus::PENGING,
                'accepted_at' => null,
                'rejected_at' => null,
            ])->save();

            return $existing->fresh();
        }

        return Connection::create([
            'requester_id' => $requester->id,
            'receiver_id' => $receiver->id,
            'status' => enConnectionStatus::PENGING,
        ]);
    }
}
