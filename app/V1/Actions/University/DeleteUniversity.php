<?php

namespace App\V1\Actions\University;

use App\Models\University;

class DeleteUniversity
{
    public function handle(University $university): void
    {
        $university->delete();
    }
}
