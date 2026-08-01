<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Models\EventRegistration;
use Illuminate\Database\Seeder;

class EventRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');

        Event::all()->each(function (Event $event) use ($userIds) {
            $count = min(rand(5, 40), (int) $event->capacity);
            if ($count <= 0) {
                return;
            }

            $registrants = $userIds->random(min($count, $userIds->count()));

    foreach ($registrants instanceof \Illuminate\Support\Collection ? $registrants : collect([$registrants]) as $userId)
{
                EventRegistration::factory()->create([
                    'event_id' => $event->id,
                    'user_id' => $userId,
                ]);
            }
        });
    }
}
