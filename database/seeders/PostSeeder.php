<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        Post::factory(20)
            ->make()
            ->each(function (Post $post) use ($userIds) {
                $post->user_id = $userIds->random();
                $post->save();
            });
    }
}
