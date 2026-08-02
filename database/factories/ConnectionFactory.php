<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory: ConnectionFactory
 *
 * Generates fake connection records representing relationship requests
 * between two users in the system (similar to friend requests or network
 * connection invitations).
 *
 * Purpose:
 * - Used for testing and seeding user-to-user connection flows.
 * - Simulates realistic scenarios where users send, accept, or reject
 *   connection requests.
 *
 * Behavior:
 * - Automatically creates requester and receiver users using their factories.
 * - Randomly assigns a connection status: pending, accepted, or rejected.
 * - Generates a realistic created_at timestamp within the last two years.
 *
 * Usage:
 * - Connection::factory()->create();
 * - Connection::factory()->count(20)->create();
 *
 * Related Models:
 * - App\Models\User
 * - App\Models\Connection
 */
class ConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed> The attributes used to create the model.
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'receiver_id'  => User::factory(),
            'status'       => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
            'created_at'   => $this->faker->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
