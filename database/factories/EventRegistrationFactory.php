<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;


class EventRegistrationFactory extends Factory
{
    public function definition(): array
    {
        $registeredAt = $this->faker->dateTimeBetween('-2 months', 'now');
        $attended = $this->faker->boolean(60);

        return [
            'registered_at' => $registeredAt,
            'attended_at' => $attended ? $this->faker->dateTimeBetween($registeredAt, 'now') : null,
            'status' => $this->faker->randomElement(['registered', 'cancelled']),
        ];
    }
}
