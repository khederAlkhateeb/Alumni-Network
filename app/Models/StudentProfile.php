<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{use HasFactory;

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

}
