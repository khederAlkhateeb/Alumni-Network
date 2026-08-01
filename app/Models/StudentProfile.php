<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Builders\StudentProfileBuilder;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable([
    'user_id',
        'major_id',
        'enrollment_number',
        'enrollment_year',
        'expected_graduation_year',
        'status',
])]
#[Appends([
    'years_until_graduation',
        'is_pending',
])]

#[UseEloquentBuilder(StudentProfileBuilder::class)]
class StudentProfile extends Model
{use HasFactory;


    protected function casts(): array
    {
        return [
        'enrollment_year'           => 'integer',
        'expected_graduation_year'  => 'integer',
        'created_at'                => 'datetime',
        ];
    }

    /************************ Relationships ************************** */




    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }



    /*
    |--------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------
    */

      protected function yearsUntilGraduation(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->expected_graduation_year) {
                return null;
            }

            return max(0, $this->expected_graduation_year - (int) now()->format('Y'));
        });
    }


    protected function isPending(): Attribute
    {
        return Attribute::get(fn () => $this->status === ProfileStatus::PENDING);
    }
}



