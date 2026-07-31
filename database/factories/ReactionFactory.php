<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReactionFactory extends Factory
{
       public function definition()
{
    return [
        'type' => $this->faker->randomElement(['like', 'insightful', 'celebrate']),
        'reactable_id' => null,
        'reactable_type' => null,
        'user_id' => null,
    ];
}

}
