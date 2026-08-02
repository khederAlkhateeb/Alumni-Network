<?php

namespace App\V1\Actions\Major;

use App\Models\Faculty;
use App\Models\Major;

/**
 * Handles the creation of a new major under a faculty.
 */
class StoreMajorAction
{
    /**
     * @param Faculty $faculty
     * @param array{name: string} $data
     * @return Major
     */
    public function handle(Faculty $faculty, array $data): Major
    {
        return $faculty->majors()->create([
            'name' => $data['name'],
        ]);
    }
}