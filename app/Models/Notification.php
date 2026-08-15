<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Hidden(['updated_at'])]
class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'related_type',
        'related_id',
        'message',
        'read_at'
    ];

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
