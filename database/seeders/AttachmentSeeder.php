<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Message;
use App\Models\Attachment;
use Illuminate\Database\Seeder;

class AttachmentSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['type' => Post::class, 'ids' => Post::inRandomOrder()->take(80)->pluck('id')],
            ['type' => Comment::class, 'ids' => Comment::inRandomOrder()->take(30)->pluck('id')],
            ['type' => Message::class, 'ids' => Message::inRandomOrder()->take(20)->pluck('id')],
        ];

        foreach ($sources as $source) {
            foreach ($source['ids'] as $id) {
                Attachment::factory()->create([
                    'attachable_id' => $id,
                    'attachable_type' => $source['type'],
                ]);
            }
        }
    }
}
