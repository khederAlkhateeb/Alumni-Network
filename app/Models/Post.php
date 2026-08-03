<?php

namespace App\Models;

use App\Enums\PostVisibility;
use App\Policies\PostPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class Post
 *
 * Represents a post created by a user in the system.
 * It can have multiple comments, attachments, and reactions.
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $user_id
 * @property string $content
 * @property string|null $image
 * @property PostVisibility $visibility
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\User $user The user who authored the post.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $comments The comments made on this post.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Attachment[] $attachments The polymorphic attachments of the post.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Reaction[] $reactions The polymorphic reactions made to the post.
 */
#[Fillable(['user_id', 'content', 'image', 'visibility'])]
#[UsePolicy(PostPolicy::class)]
class Post extends Model
{
    use HasFactory;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => PostVisibility::class,
        ];
    }

    /**
     * Get the user that authored the post.
     *
     * Note: Fixed from hasOne() to belongsTo() since the 'posts' table
     * holds the foreign key ('user_id').
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for the post.
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Get all attachments associated with the post.
     *
     * This defines a polymorphic, one-to-many relationship.
     *
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get all reactions associated with the post.
     *
     * This defines a polymorphic, one-to-many relationship.
     *
     * @return MorphMany
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    public function scopeFromConnections($query, array $connectionIds)
    {
        return $query->whereIn('user_id', $connectionIds);
    }

    public function scopeUniversityAnnouncements($query, $universityId)
    {
        return $query->where('visibility', PostVisibility::University->value)
            ->whereHas('user.alumniProfile.major.faculty',
                fn($q) => $q->where('university_id', $universityId));
    }

    public function scopeFromSameUniversityAlumni($query, $universityId)
    {
        return $query->where('visibility', PostVisibility::Public->value)
            ->whereHas('user.alumniProfile.major.faculty',
                fn($q) => $q->where('university_id', $universityId));
    }
}
