<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Reaction;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        $types = ['like', 'insightful', 'celebrate'];

              Post::all()->each(function (Post $post) use ($userIds, $types) {

            $count = rand(0, 3);
            if ($count === 0) {
                return;
            }

            $reactors = $userIds->random(min($count, $userIds->count()));

            foreach ($reactors as $userId) {
                Reaction::create([
                    'reactable_id'   => $post->id,
                    'reactable_type' => Post::class,
                    'user_id'        => $userId,
                    'type'           => $types[array_rand($types)],
                ]);
            }
        });


        Comment::all()->each(function (Comment $comment) use ($userIds, $types) {

            $count = rand(0, 3);
            if ($count === 0) {
                return;
            }

            $reactors = $userIds->random(min($count, $userIds->count()));

            foreach ($reactors as $userId) {
                Reaction::create([
                    'reactable_id'   => $comment->id,
                    'reactable_type' => Comment::class,
                    'user_id'        => $userId,
                    'type'           => $types[array_rand($types)],
                ]);
            }
        });
    }
}
