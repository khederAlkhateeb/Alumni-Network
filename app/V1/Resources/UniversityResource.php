<?php

namespace App\V1\Resources;

use App\Models\University;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin University */
class UniversityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'country'    => $this->country,
            'website'    => $this->website,
            'logo'       => $this->logo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
