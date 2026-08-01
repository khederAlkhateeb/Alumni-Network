<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        Conversation::all()->each(function (Conversation $conversation) {

            $count = rand(1, 10);

            for ($i = 0; $i < $count; $i++) {
                Message::factory()
                    ->withConversation($conversation)
                    ->create();
            }
        });
    }
}
