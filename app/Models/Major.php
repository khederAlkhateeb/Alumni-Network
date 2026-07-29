<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Major extends Model
{
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }
}
