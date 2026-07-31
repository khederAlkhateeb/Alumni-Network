<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+3 months');
        $type = $this->faker->randomElement(['campus', 'online', 'hybrid']);

        return [
            'university_id' => University::factory(),
            'title' => $this->faker->randomElement([
                'Annual Alumni Networking Night',
                'Career Fair 2026',
                'Tech Talk: AI in Industry',
                'Homecoming Reunion',
                'Startup Pitch Day',
            ]),
            'description' => $this->faker->paragraph(),
            'type' => $type,
            'location' => $type === 'online' ? null : $this->faker->address(),
            'meeting_link' => $type === 'online' || $type === 'hybrid' ? $this->faker->url() : null,
            'start_date' => $start,
            'end_date' => (clone $start)->modify('+3 hours'),
            'capacity' => $this->faker->numberBetween(30, 500),
            'status' => $this->faker->randomElement(['upcoming', 'ongoing', 'completed', 'cancelled']),
        ];
    }
}
