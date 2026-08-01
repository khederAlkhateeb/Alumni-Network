<?php

namespace Database\Seeders;

use App\Models\University;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        University::all()->each(function (University $university) {
            Event::factory()
                ->count(rand(2, 5))
                ->create(['university_id' => $university->id]);
        });
    }
}
