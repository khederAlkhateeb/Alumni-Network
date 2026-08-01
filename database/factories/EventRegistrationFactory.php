<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: EventRegistrationFactory
 *
 * Generates realistic event registration records for testing and seeding.
 *
 * Purpose:
 * - Simulates user registrations for university events.
 * - Supports attendance tracking and registration status flows.
 *
 * Behavior:
 * - Generates a registration timestamp within the last two months.
 * - Randomly determines whether the user attended the event.
 * - If attended, assigns an attended_at timestamp after registration.
 * - Randomly assigns a registration status (registered or cancelled).
 *
 * Usage:
 * - EventRegistration::factory()->create();
 * - EventRegistration::factory()->count(20)->create();
 *
 * Related Models:
 * - App\Models\Event
 * - App\Models\User
 * - App\Models\EventRegistration
 */
class EventRegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        $registeredAt = $this->faker->dateTimeBetween('-2 months', 'now');
        $attended     = $this->faker->boolean(60); // 60% chance of attendance

        return [
            'registered_at' => $registeredAt,

            // attended_at is only set if the user actually attended
            'attended_at'   => $attended
                ? $this->faker->dateTimeBetween($registeredAt, 'now')
                : null,

            'status'        => $this->faker->randomElement(['registered', 'cancelled']),
        ];
    }
}
