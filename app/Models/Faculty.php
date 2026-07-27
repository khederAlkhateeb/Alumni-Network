<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faculty extends Model
{
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
