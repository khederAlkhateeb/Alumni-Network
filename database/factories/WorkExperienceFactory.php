<?php

namespace Database\Factories;

use App\Models\AlumniProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * --------------------------------------------------------------------------
 * WorkExperienceFactory
 * --------------------------------------------------------------------------
 *
 * Factory responsible for generating realistic fake data for the
 * WorkExperience model. Used in:
 * - Database seeding
 * - Automated tests
 * - Local development environments
 *
 * Relationships:
 * - alumni_profile_id → AlumniProfile::factory()
 *
 * Fields:
 * - company: Fake company name
 * - job_title: Fake job title
 * - start_date: Random date between 1–8 years ago
 * - end_date: Either null (40% chance) or a date between start_date and now
 *
 * Notes:
 * - `start_date` simulates realistic employment history.
 * - `end_date = null` represents ongoing employment.
 * - `end_date` is generated only if the job is not ongoing.
 */
class WorkExperienceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-8 years', '-1 year');
        $end = $this->faker->boolean(40)
            ? null
            : $this->faker->dateTimeBetween($start, 'now');

        return [
            'alumni_profile_id' => AlumniProfile::factory(),
            'company' => $this->faker->company(),
            'job_title' => $this->faker->jobTitle(),
            'start_date' => $start,
            'end_date' => $end,
        ];
    }
}
