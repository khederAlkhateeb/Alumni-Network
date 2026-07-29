<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class WorkExperience
 *
 * Represents a professional work experience entry associated with an alumni profile.
 *
 * @property int $id
 * @property int $alumni_profile_id
 * @property string $company
 * @property string $job_title
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 *
 * @property-read int $duration_in_months
 * @property-read string $duration_label
 *
 * @property-read AlumniProfile $alumniProfile
 *
 * @package App\Models
 */
#[Fillable([
    'alumni_profile_id',
    'company',
    'job_title',
    'start_date',
    'end_date',
])]
#[Appends(['duration_in_months', 'duration_label'])]
class WorkExperience extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /************************ Relationships ************************** */

    /**
     * Get the alumni profile that owns the work experience record.
     *
     * @return BelongsTo<AlumniProfile, WorkExperience>
     */
    public function alumniProfile(): BelongsTo
    {
        return $this->belongsTo(AlumniProfile::class);
    }

    /************************ Accessors & Mutators ************************** */

    /**
     * Accessor for total duration in months between start_date and end_date (or present day).
     *
     * @return Attribute<int, void>
     */
    protected function durationInMonths(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->start_date) {
                    return 0;
                }

                $end = $this->end_date ?? Carbon::now();

                return (int) $this->start_date->diffInMonths($end);
            },
        );
    }

    /**
     * Accessor for a human-readable duration label (e.g., "2 yrs 3 mos", "1 yr", "5 mos").
     *
     * @return Attribute<string, void>
     */
    protected function durationLabel(): Attribute
    {
        return Attribute::make(
            get: function () {
                $months = $this->duration_in_months;

                if ($months <= 0) {
                    return '0 mos';
                }

                $years = intdiv($months, 12);
                $remainingMonths = $months % 12;

                if ($years > 0 && $remainingMonths > 0) {
                    return "{$years} yr".($years > 1 ? 's' : '')." {$remainingMonths} mo".($remainingMonths > 1 ? 's' : '');
                }

                if ($years > 0) {
                    return "{$years} yr".($years > 1 ? 's' : '');
                }

                return "{$remainingMonths} mo".($remainingMonths > 1 ? 's' : '');
            },
        );
    }
}
