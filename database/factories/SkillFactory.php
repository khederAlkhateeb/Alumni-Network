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
            'name'     => $this->faker->unique()->randomElement([
                'Laravel', 'PHP', 'JavaScript', 'React', 'Vue.js',
                'Node.js', 'Python', 'Django', 'MySQL', 'PostgreSQL',
                'Docker', 'Kubernetes', 'AWS', 'Git', 'Figma',
                'UI/UX Design', 'Project Management', 'Agile/Scrum',
                'Data Analysis', 'Machine Learning', 'DevOps',
                'System Design', 'REST APIs', 'GraphQL', 'Redis',
            ]),
            'category' => $this->faker->randomElement([
                'Backend', 'Frontend', 'Design', 'Management', 'DevOps', 'Data',
            ]),
        ];
    }
}
