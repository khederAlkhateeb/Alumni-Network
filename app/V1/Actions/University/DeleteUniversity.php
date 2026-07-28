<?php

namespace App\V1\Actions\University;

use App\Models\University;

class DeleteUniversity
{
    /**
     * Delete (soft-delete) the given university.
     *
     * @param  University $university
     * @return bool|null
     */
    public function handle(University $university): ?bool
    {
        return $university->delete();
    }
}
