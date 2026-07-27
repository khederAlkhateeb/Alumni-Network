<?php

namespace App\V1\Actions\University;

use App\Models\University;

class CreateUniversity
{
    public function handle(array $data): University
    {
        $data['created_by'] = auth()->id();

        return University::create($data);
    }
}
