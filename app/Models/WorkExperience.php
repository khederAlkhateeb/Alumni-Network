<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

#[Fillable([
    'alumni_profile_id',
    'company',
    'job_title',
    'start_date',
    'end_date',
])]
#[Appends([ 'duration_in_months', 'duration_label'])]
class WorkExperience extends Model
{
   use HasFactory;
    public $timestamps = false;

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function alumniProfile(): BelongsTo
    {
        return $this->belongsTo(AlumniProfile::class);
    }



    /**
     * Duration in months between start_date and (end_date or now if current).
     */
    protected function durationInMonths(): Attribute
    {
        return Attribute::make(
            get: function () {
                $end = $this->end_date ?? Carbon::now();

                return (int) $this->start_date->diffInMonths($end);
            },
        );
    }


    protected function durationLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $months = $this->duration_in_months;
                $years  = intdiv($months, 12);
                $remainingMonths = $months % 12;

                if ($years > 0 && $remainingMonths > 0) {
                    return "{$years} yr" . ($years > 1 ? 's' : '') . " {$remainingMonths} mo" . ($remainingMonths > 1 ? 's' : '');
                }

                if ($years > 0) {
                    return "{$years} yr" . ($years > 1 ? 's' : '');
                }

                return "{$remainingMonths} mo" . ($remainingMonths > 1 ? 's' : '');
            },
        );
    }
}
