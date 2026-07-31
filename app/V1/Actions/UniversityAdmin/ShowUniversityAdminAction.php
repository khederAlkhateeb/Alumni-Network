<?php

namespace App\V1\Actions\UniversityAdmin;

use Illuminate\Http\Request;
use App\Models\UniversityAdmin;

class ShowUniversityAdminAction
{

    /**
     * Show university admin info
     *
     * @param UniversityAdmin $universityAdmin
     * @return array
     */
    public function handle(UniversityAdmin $universityAdmin): array{

        return $universityAdmin->load(['user', 'university'])->toArray();
    }
}