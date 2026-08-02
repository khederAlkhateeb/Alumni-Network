<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faculty extends Model
{use HasFactory;

    public $timestamps = false;
    protected $fillable = [
        'name',
        'university_id',
    ];

    /**
     * The university that owns this faculty.
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function majors()
    {
        return $this->hasMany(Major::class);
    }
}
