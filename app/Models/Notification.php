<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Notification
 *
 * Handles system alerts and event notifications directed at users.
 * Conceals internal polymorphic morph targets to streamline payload structures.
 *
 * @package App\Models
 */
class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'related_type',
        'related_id',
        'message',
        'read_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'related_type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * The user who owns this notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The polymorphic subject this notification is about
     * (e.g. a Connection, JobListing, MentorshipRequest, Event...).
     */
    public function related()
    {
        return $this->morphTo();
    }

    /**
     *  Mark this notification as read by setting the `read_at` timestamp.
     *  If the notification is already marked as read, this method does nothing.
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    protected $guarded = [];
}
