<?php

namespace App\Models;

use App\Builders\EventBuilder;
use App\Models\University;
use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Event
 *
 * Represents a university event (career day, workshop, lecture, etc.).
 * Each event belongs to a single university and can have many user
 * registrations. Events can be on-campus, online, or hybrid, and move
 * through a lifecycle of statuses (upcoming, ongoing, completed,
 * cancelled).
 *
 * @property int $id
 * @property int $university_id
 * @property string $title
 * @property string|null $description
 * @property string $type            campus|online|hybrid
 * @property string|null $location
 * @property string|null $meeting_link
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int|null $capacity
 * @property string $status          upcoming|ongoing|completed|cancelled
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @property-read University $university
 * @property-read \Illuminate\Database\Eloquent\Collection<int, EventRegistration> $registrations
 * @property-read int|null $registrations_count
 *
 * @method static EventBuilder query()
 * @method static EventBuilder upcoming()
 * @method static EventBuilder ongoing()
 * @method static EventBuilder completed()
 * @method static EventBuilder cancelled()
 * @method static EventBuilder status(string $status)
 * @method static EventBuilder ofType(string $type)
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
     * Available event type values (aligned with the "type" enum column).
     */
    public const TYPE_ON_CAMPUS = 'campus';
    public const TYPE_ONLINE = 'online';
    public const TYPE_HYBRID = 'hybrid';

    /**
     * Available event status values (aligned with the "status" enum column).
     */
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

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
            'end_date'   => 'datetime',
            'capacity'   => 'integer',
        ];
    }

    /************************ Relationships ************************** */

    /**
     * The university this event belongs to.
     *
     * Required for scoped route model binding
     * (Route::scopeBindings() on "universities/{university}/events/{event}").
     *
     * @return BelongsTo<University, Event>
     */
    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    /**
     * The registrations (attendees) associated with this event.
     *
     * @return HasMany<EventRegistration>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /************************ Accessors & Helpers ************************** */

    /**
     * Determine whether the event has reached its maximum capacity.
     * An event with a null capacity is considered unlimited.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        if (is_null($this->capacity)) {
            return false;
        }

        return $this->registrations()->count() >= $this->capacity;
    }

    /**
     * Determine whether the event has already started.
     *
     * @return bool
     */
    public function hasStarted(): bool
    {
        return now()->isAfter($this->start_date);
    }

    /**
     * Determine whether the event is currently taking place
     * (i.e. "now" falls between its start and end dates).
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return now()->between($this->start_date, $this->end_date);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return EventBuilder
     */
    public function newEloquentBuilder($query): EventBuilder
    {
        return new EventBuilder($query);
    }
}
