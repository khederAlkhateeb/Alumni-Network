<?php

namespace Database\Seeders;

use App\Models\JobListing;
use App\Models\User;
use App\Models\Connection;
use App\Models\Post;
use App\Models\Job;
use App\Models\MentorshipRequest;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id');
        $sources = [
            ['type' => Connection::class, 'ids' => Connection::pluck('id'), 'notif_type' => 'connection_request'],
            ['type' => Post::class, 'ids' => Post::pluck('id'), 'notif_type' => 'post_reaction'],
            ['type' => JobListing::class, 'ids' => JobListing::pluck('id'), 'notif_type' => 'job_application'],
            ['type' => MentorshipRequest::class, 'ids' => MentorshipRequest::pluck('id'), 'notif_type' => 'mentorship_request'],
        ];

        $sources = array_values(array_filter($sources, fn ($source) => $source['ids']->isNotEmpty()));

        if (empty($sources)) {
            $this->command?->warn('no source for send   NotificationSeeder.');
            return;
        }

        for ($i = 0; $i < 250; $i++) {
            $source = $sources[array_rand($sources)];

            Notification::factory()->create([
                'user_id' => $userIds->random(),
                'type' => $source['notif_type'],
                'related_id' => $source['ids']->random(),
                'related_type' => $source['type'],
            ]);
        }
    }
}
