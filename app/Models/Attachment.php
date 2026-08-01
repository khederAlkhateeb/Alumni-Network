<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([   'file_path',])]
class Attachment extends Model
{use HasFactory;


    const UPDATED_AT = null;




    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
