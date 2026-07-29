<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }
}
