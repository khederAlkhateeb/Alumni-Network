<?php

namespace Database\Factories;

use App\Models\University;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'university_id' => University::factory(),
            'report_type' => $this->faker->randomElement([
                'alumni_growth', 'engagement_overview', 'jobs_activity', 'mentorship_summary', 'event_attendance',
            ]),
            'snapshot_data' => json_encode([
                'total_alumni' => $this->faker->numberBetween(100, 5000),
                'active_last_30_days' => $this->faker->numberBetween(10, 1000),
                'generated_note' => 'fake data from factory',
            ]),
            'generated_by' => User::factory(),
            'generated_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
