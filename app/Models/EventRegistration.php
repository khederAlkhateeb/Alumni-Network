<?php

namespace App\Models;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
|--------------------------------------------------------------------------
| Event Registration Model
|--------------------------------------------------------------------------
|
| Represents a single user's registration to an event. Tracks the
| moment the user registered, and (optionally) the moment their
| attendance was recorded by a University Admin during the event.
|
| @property int $id
| @property int $event_id
| @property int $user_id
| @property \Illuminate\Support\Carbon $registered_at
| @property \Illuminate\Support\Carbon|null $attended_at
| @property \Illuminate\Support\Carbon $created_at
| @property \Illuminate\Support\Carbon $updated_at
|
| @property-read Event $event
| @property-read User $user
|
*/
class EventRegistration extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'user_id',
        'registered_at',
        'attended_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'attended_at'   => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The event this registration belongs to.
     *
     * @return BelongsTo<Event, EventRegistration>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * The user who registered for the event.
     *
     * @return BelongsTo<User, EventRegistration>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Determine whether the user's attendance has been recorded.
     *
     * @return bool
     */
    public function hasAttended(): bool
    {
        return !is_null($this->attended_at);
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     *
     * Filter registerations belonging to a specific university.
     * @param Builder $query
     * @param int $universityId
     * @return Builder
     */
    public function scopeForUniversity(Builder $query, int $universityId): Builder
    {
        return $query->where('university_id', $universityId);
    }

    /**
     * Only include registrations for the given event.
     *
     * @param  Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $event_id
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeForEvent(Builder $query, int $event_id): Builder
    {
        return $query->where('event_id', $event_id);
    }
}
