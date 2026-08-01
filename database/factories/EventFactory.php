<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: EventFactory
 *
 * Generates realistic event records for testing and database seeding.
 *
 * Purpose:
 * - Simulates university events such as networking nights, tech talks,
 *   career fairs, and reunions.
 * - Supports multiple event types: campus, online, and hybrid.
 *
 * Behavior:
 * - Automatically creates a University using its factory.
 * - Randomly selects an event title from a predefined list.
 * - Generates a description, type, location, meeting link, and dates.
 * - Ensures logical consistency:
 *      - Online events have no physical location.
 *      - Hybrid and online events include a meeting link.
 *      - End date is always 3 hours after the start date.
 *
 * Usage:
 * - Event::factory()->create();
 * - Event::factory()->count(10)->create();
 *
 * Related Models:
 * - App\Models\Event
 * - App\Models\University
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
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

            // Physical location only for campus or hybrid events
            'location' => $type === 'online' ? null : $this->faker->address(),

            // Meeting link for online or hybrid events
            'meeting_link' => $type === 'online' || $type === 'hybrid'
                ? $this->faker->url()
                : null,

            'start_date' => $start,
            'end_date'   => (clone $start)->modify('+3 hours'),

            'capacity' => $this->faker->numberBetween(30, 500),

            'status' => $this->faker->randomElement([
                'upcoming',
                'ongoing',
                'completed',
                'cancelled',
            ]),
        ];
    }
}
