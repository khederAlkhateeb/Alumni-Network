<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use App\Models\WorkExperience;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkExperienceFactory extends Factory
{
    protected $model = WorkExperience::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-8 years', '-6 months');

        return [
            'alumni_profile_id' => AlumniProfile::factory(),
            'company'           => $this->faker->company(),
            'job_title'         => $this->faker->jobTitle(),
            'start_date'        => $startDate,
            'end_date'          => $this->faker->boolean(70)
                ? $this->faker->dateTimeBetween($startDate, 'now')
                : null,
        ];
    }

    /**
     * Force this experience to be the current job (end_date = null).
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
        ]);
    }

    /**
     * Force this experience to be a past job with a specific end date.
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => $this->faker->dateTimeBetween($attributes['start_date'], 'now'),
        ]);
    }
}
