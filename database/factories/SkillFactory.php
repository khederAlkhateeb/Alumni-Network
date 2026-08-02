<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(2, true)),
            'category' => $this->faker->randomElement([
                'Backend', 'Frontend', 'Design', 'Management', 'DevOps', 'Data',
            ]),
        ];
    }
}
