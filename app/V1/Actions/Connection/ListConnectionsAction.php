<?php

namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lists connections for the authenticated user, optionally filtered by status.
 */
class ListConnectionsAction
{
    public function handle(string|array|enConnectionStatus $status = enConnectionStatus::ACCEPTED->value): Collection
    {
        return Connection::query()
            ->forUser(auth()->id())
            ->when($status !== '', fn ($query) => $query->filterByStatus($status))
            ->with(['sender', 'receiver'])
            ->get();
    }
}
