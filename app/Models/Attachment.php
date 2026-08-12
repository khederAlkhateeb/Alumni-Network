<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;


#[Fillable(['file_path',])]
#[Appends(['url'])]
class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'file_path',
    ];

    /**
     * Interact with the attachment's public URL.
     *
     * @return Attribute
     */
    protected function url(): Attribute
    {
        return Attribute::get(
            fn() => $this->file_path ? Storage::url($this->file_path) : null
        );
    }

    const UPDATED_AT = null;




    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
