<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UniversityAdmin extends Model
{
    protected $fillable = [
        'user_id',
        'university_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function university()
    {
        return $this->belongsTo(University::class);
    }
}
