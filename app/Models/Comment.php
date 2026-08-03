<?php

namespace App\Models;

use App\Policies\CommentPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Comment
 *
 * Represents a comment made by a user on a specific post.
 * This model supports hierarchical comments (nested replies) via the parent_comment_id.
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $post_id
 * @property int $user_id
 * @property string $content
 * @property int|null $parent_comment_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \App\Models\Post $post The post that this comment belongs to.
 * @property-read \App\Models\User $user The user who authored the comment.
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Comment[] $replies The child comments (replies) associated with this comment.
 */
#[Fillable(['post_id', 'user_id', 'content', 'parent_comment_id'])]
#[UsePolicy(CommentPolicy::class)]
class Comment extends Model
{
    use HasFactory;
    /**
     * Get the post that the comment belongs to.
     *
     * @return BelongsTo
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who authored the comment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the replies (child comments) for this comment.
     *
     * This defines a self-referencing one-to-many relationship.
     *
     * @return HasMany
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_comment_id');
    }
}
