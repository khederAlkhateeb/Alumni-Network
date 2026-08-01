<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Connection;
use Illuminate\Database\Seeder;

class ConnectionSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        for ($i = 0; $i < 150; $i++) {
            [$requester, $receiver] = $userIds->random(2)->values();

       $exists = Connection::where(function ($q) use ($requester, $receiver) {
        $q->where('requester_id', $requester)
          ->where('receiver_id', $receiver);
    })
    ->orWhere(function ($q) use ($requester, $receiver) {
        $q->where('requester_id', $receiver)
          ->where('receiver_id', $requester);
    })
    ->exists();

if ($exists) {
    continue;
}
            Connection::factory()->create([
                'requester_id' => $requester,
                'receiver_id' => $receiver,
            ]);
        }
    }
}
