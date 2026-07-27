<?php

namespace App\V1\Actions\University;

use App\Models\University;

class UpdateUniversity
{
    public function handle(University $university, array $data): University
    {
        $data['updated_by'] = auth()->id();

        $university->update($data);

        return $university->fresh();
    }
}
