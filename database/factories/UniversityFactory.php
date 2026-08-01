<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UniversityFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->company() . ' University';

        return [
            'name' => $name,
            'country' => $this->faker->country(),
            'website' => 'https://' . str($name)->slug() . '.edu',
            'logo' => null,
            // 'created_at' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
        ];
    }
}
