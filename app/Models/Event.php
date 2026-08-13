<?php

namespace App\Models;

use App\Builders\EventBuilder;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\University;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Event
 *
 * @property int $id
 * @property int $university_id
 * @property string $title
 * @property string|null $description
 * @property EventType $type
 * @property string|null $location
 * @property string|null $meeting_link
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int|null $capacity
 * @property EventStatus $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read University $university
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventRegistration> $registrations
 * @property-read int|null $registrations_count
 *
 * @method static EventBuilder query()
 * @method static EventBuilder status(EventStatus $status)
 * @method static EventBuilder ofType(EventType $type)
 * @method static EventBuilder forUniversity(int $universityId)
 * @method static EventBuilder startingBetween(\DateTimeInterface|string $from, \DateTimeInterface|string $to)
 * @method static EventBuilder notFull()
 *
 * @package App\Models
 */
class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'university_id',
        'title',
        'description',
        'type',
        'location',
        'meeting_link',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'capacity' => 'integer',
            'status' => EventStatus::class,
            'type' => EventType::class,
        ];
    }

    /************************ Relationships ************************** */

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /************************ Accessors & Helpers ************************** */

    public function isFull(): bool
    {
        if (is_null($this->capacity)) {
            return false;
        }

        return $this->registrations()->count() >= $this->capacity;
    }

    public function hasStarted(): bool
    {
        return now()->isAfter($this->start_date);
    }

    public function isOngoing(): bool
    {
        return now()->between($this->start_date, $this->end_date);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return EventBuilder
     */
    public function newEloquentBuilder($query): EventBuilder
    {
        return new EventBuilder($query);
    }
}
