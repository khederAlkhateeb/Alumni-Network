<?php

namespace App\Policies;

use App\Enums\ProfileStatus;
use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Only active alumni are allowed to create posts.
     * Pending/suspended users must not be able to post,
     * per business rule: "a pending user sees no content
     * and cannot interact with anyone."
     */
    public function create(User $user): bool
    {
        return $user->hasRole('alumni')
            && $user->alumniProfile?->status === ProfileStatus::ACTIVE;
    }

    /**
     * Only the post's owner can update it.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * The post's owner, or a University Admin (moderation),
     * can delete a post.
     */
    public function delete(User $user, Post $post): bool
    {
        if ($user->id === $post->user_id) {
            return true;
        }

        return $user->hasRole('uni_admin');
        // TODO:   university scoping ،
    }

    /**
     *  TODO
     * Any authenticated & active user can view a single post.
     * Visibility rules (public/connections/university) in separete place (Scope/Query)،
     */
    public function view(User $user, Post $post): bool
    {
        if(!$user->is_active)
        {
            return false;
        }
        return true;
    }
}
