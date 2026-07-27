<?php

namespace App\V1\Actions\University;

use App\Models\University;
use Illuminate\Pagination\LengthAwarePaginator;

class ListUniversities
{
    public function handle(array $data): LengthAwarePaginator
    {
        return University::paginate($data['per_page'] ?? 15);
    }
}
