<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{use HasFactory;

    //
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
