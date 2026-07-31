<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentProfileFactory extends Factory
{
    public function definition(): array
    {
        $enrollmentYear = $this->faker->numberBetween(2019, 2025);

        return [
            'user_id' => User::factory(),
            'major_id' => Major::factory(),
            'enrollment_number' =>  $this->faker->unique()->numerify('######'),
            'enrollment_year' => $enrollmentYear,
            'expected_graduation_year' => $enrollmentYear + 4,
            'status' => $this->faker->randomElement(['pending', 'active', 'suspended']),
            'created_at' => now(),
        ];
    }
}
