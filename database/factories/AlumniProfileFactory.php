<?php
namespace Database\Factories;

use App\Models\AlumniProfile;
use App\Models\User;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlumniProfile>
 */
class AlumniProfileFactory extends Factory
{
    protected $model = AlumniProfile::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'major_id'          => Major::factory(),
            'student_number'    => $this->faker->unique()->numerify('202#####'),
            'graduation_year'   => $this->faker->numberBetween(2018, 2025),
            'current_job_title' => $this->faker->jobTitle(),
            'current_company'   => $this->faker->company(),
            'linkedin_url'      => 'https://linkedin.com/in/' . $this->faker->userName(),
            'bio'               => $this->faker->paragraph(2),
            'city'              => $this->faker->city(),
            'country'           => $this->faker->country(),
            'status'            => 'active',
            'is_open_to_mentor' => $this->faker->boolean(40),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | States ( for Testing)
    |--------------------------------------------------------------------------
    */

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }


    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }


    public function openToMentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_open_to_mentor' => true,
            'status'            => 'active',
        ]);
    }
}
