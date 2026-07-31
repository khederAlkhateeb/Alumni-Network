<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Major extends Model
{use HasFactory;

   public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

}
