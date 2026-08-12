<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a link between a local user account and an external social provider.
 *
 * This model stores the association between a user in the system and their
 * identity on external OAuth providers (e.g., Google, Facebook, GitHub).
 * It enables users to log in via multiple providers while maintaining a single
 * local user record.
 *
 * @package App\Models
 *
 * @property int                             $id             Unique identifier for the social link.
 * @property string                          $provider_name  Name of the OAuth provider (e.g., 'google').
 * @property string                          $provider_id    Unique user identifier returned by the provider.
 * @property int                             $user_id        Foreign key referencing the local `users` table.
 * @property \Illuminate\Support\Carbon|null $created_at     Timestamp when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at     Timestamp when the record was last updated.
 *
 * @property-read User $user The local user that owns this social account link.
 */
#[Fillable(['provider_name', 'user_id', 'provider_id'])]
class LinkedSocialAccount extends Model
{
    use HasFactory;

    /**
     * Define the inverse one-to-many relationship with the User model.
     *
     * This method establishes that each social account link belongs to exactly
     * one user. It allows eager loading and lazy loading of the associated
     * user via `$socialAccount->user`.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
