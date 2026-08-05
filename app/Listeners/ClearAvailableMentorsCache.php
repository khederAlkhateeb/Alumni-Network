<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearAvailableMentorsCache
{
    public function handle(object $event): void
    {

        Cache::tags(['available_mentors'])->flush();
    }
}
