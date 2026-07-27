<?php

namespace App\V1\Actions\University;

use App\Models\University;

class GetUniversity
{
    public function handle(University $university): University
    {
        return $university;
    }
}
