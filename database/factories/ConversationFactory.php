<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
       public function definition(): array
    {
        return [
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}

