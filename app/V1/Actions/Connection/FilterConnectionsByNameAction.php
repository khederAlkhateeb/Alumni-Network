<?php
namespace App\V1\Actions\Connection;

use App\Enums\enConnectionStatus;
use App\Models\Connection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FilterConnectionsByNameAction
{
    /**
     * Execute the action to filter authenticated user's connections by name.
     *
     * @param string|null $searchTerm
     * @param int|null $perPage
     * @return LengthAwarePaginator
     */
    public function handle(?string $searchTerm = null, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?? config('app.pagination.per_page', 15);

        return Connection::query()
            ->forUser(auth()->id())
            ->where('status', enConnectionStatus::ACCEPTED)
            ->filterByName($searchTerm)
            ->with(['sender', 'receiver'])
            ->latest('created_at')
            ->paginate((int) $perPage);
    }
}
