<?php

namespace App\V1\Actions\University;

use App\Models\Scopes\UniversityScope;
use App\Models\University;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUniversities
{

    public function handle(int $per_page = 15): LengthAwarePaginator
    {
        return University::withoutGlobalScope(UniversityScope::class)->paginate($per_page ?? config('app.pagination.per_page'));
    }
}
