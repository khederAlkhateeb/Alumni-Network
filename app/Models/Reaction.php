<?php

namespace App\Models;

use App\Enums\ReactionType;
use App\Policies\ReactPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Class Reaction
 *
 * Represents a user's reaction (e.g., like, love, etc.) to a specific entity (like a Post or Comment).
 * This model uses polymorphic relations to attach to any 'reactable' model.
 *
 * @package App\Models
 *
 * @property int $id
 * @property int $user_id
 * @property string $reactable_type
 * @property int $reactable_id
 * @property ReactionType $type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read Model $reactable The entity that the user reacted to.
 * @property-read User $user The user who created the reaction.
 */
#[Fillable(['reactable_id', 'reactable_type', 'type', 'user_id'])]
#[UsePolicy(ReactPolicy::class)]
class Reaction extends Model
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
            'type' => ReactionType::class,
        ];
    }


    /**
     * Get the parent reactable model (Post, Comment, etc.).
     *
     * This defines a polymorphic, one-to-many relationship.
     *
     * @return MorphTo
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the reaction.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
