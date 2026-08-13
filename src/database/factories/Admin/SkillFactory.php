<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'match_type' => 'any',
            'is_active' => true,
            'sort_order' => 0,
            'version' => 1,
        ];
    }
}
