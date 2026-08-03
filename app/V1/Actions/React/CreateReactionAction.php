<?php

namespace App\V1\Actions\React;

use App\Models\Reaction;
use App\Models\User;

class CreateReactionAction
{
    /**
     * Create a reaction, or update its type if the user already
     * reacted to the same target (post/comment) before — mirrors
     * the common "toggle reaction type" behavior on social platforms.
     *
     * Relies on a registered morph map ('post' => Post::class,
     * 'comment' => Comment::class) so reactable_type is stored as
     * a short alias and resolved automatically by Eloquent.
     *
     * @param User  $user The user creating/updating the reaction.
     * @param array $data Validated data: reactable_id, reactable_type, type.
     *
     * @return Reaction The reaction, with its reactable relation loaded.
     */
    public function handle(User $user, array $data): Reaction
    {
        $reaction = Reaction::updateOrCreate(
            [
                'user_id'        => $user->id,
                'reactable_id'   => $data['reactable_id'],
                'reactable_type' => $data['reactable_type'],
            ],
            [
                'type' => $data['type'],
            ]
        );

        return $reaction->load('reactable');
    }
}
