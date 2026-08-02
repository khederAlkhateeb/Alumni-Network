<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        for ($i = 0; $i < 80; $i++) {


            [$u1, $u2] = $userIds->random(2)->values();

                $pair = collect([$u1, $u2])->sort()->values();
            if (Conversation::where('user_one_id', $pair[0])
                ->where('user_two_id', $pair[1])
                ->exists()) {
                continue;
            }

            Conversation::factory()->create([
                'user_one_id' => $pair[0],
                'user_two_id' => $pair[1],
            ]);
        }
    }
}
