<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkExperienceFactory extends Factory
{
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-8 years', '-1 year');
        $end = $this->faker->boolean(40) ? null : $this->faker->dateTimeBetween($start, 'now');

        return [
            'alumni_profile_id' => AlumniProfile::factory(),
            'company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'start_date' => $start,
            'end_date' => $end,
        ];
    }
}
