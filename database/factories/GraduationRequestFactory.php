<?php

namespace Database\Factories;

use App\Models\GraduationRequest;
use App\Models\User;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GraduationRequest>
 */
class GraduationRequestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GraduationRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'student_profile_id' => StudentProfile::factory(),
            'certificate_path' => 'certificates/sample.pdf',
            'status' => 'pending',
            'rejection_reason' => null,
        ];
    }
}
