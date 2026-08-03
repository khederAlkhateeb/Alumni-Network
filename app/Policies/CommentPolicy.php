<?php

namespace App\Policies;

use App\Enums\ProfileStatus;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Class CommentPolicy
 *
 * Handles authorization rules for comment-related actions.
 *
 * @package App\Policies
 */
class CommentPolicy
{
    /**
     * Both Alumni and Students can Comment, as long as their
     * profile status is active (pending/suspended users cannot
     * interact with any content).
     *
     * Uses the null-safe operator (?->) since a user might carry
     * the 'alumni'/'student' role via Spatie while their profile
     * record hasn't been created yet (or was removed) — accessing
     * ->status directly on a null profile would throw a fatal error.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('alumni') && $user->alumniProfile?->status === ProfileStatus::ACTIVE) {
            return true;
        }

        return $user->hasRole('student') && $user->studentProfile?->status === ProfileStatus::ACTIVE;
    }

    /**
     * Determine whether the user can delete a specific comment.
     *
     * Users can delete a comment if they are the original author of the comment.
     * Additionally, users with the 'uni_admin' role have administrative privileges
     * to delete any comment.
     *
     * @param User    $user    The currently authenticated user.
     * @param Comment $comment The comment model instance to be deleted.
     *
     * @return bool Returns true if the user owns the comment or is a university admin.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Allow if the user is the owner of the comment
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Allow if the user is a university administrator
        return $user->hasRole('uni_admin');
    }
}
