<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        Post::all()->each(function (Post $post) use ($userIds) {
            $topLevelCount = rand(0, 5);

            $topLevelComments = collect();

            for ($i = 0; $i < $topLevelCount; $i++) {
                $topLevelComments->push(
                    Comment::factory()->create([
                        'post_id' => $post->id,
                        'user_id' => $userIds->random(),
                    ])
                );
            }
            foreach ($topLevelComments as $parentComment) {
                $repliesCount = rand(0, 2);

                for ($i = 0; $i < $repliesCount; $i++) {
                    Comment::factory()
                        ->reply($parentComment->id, $post->id)
                        ->create(['user_id' => $userIds->random()]);
                }
            }
        });
    }
}
