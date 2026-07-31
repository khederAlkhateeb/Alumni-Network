<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'receiver_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
