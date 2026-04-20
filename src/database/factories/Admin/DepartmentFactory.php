<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Department;
use App\Models\Admin\DepartmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->company().' Department';

        return [
            'name' => $name,
            'slug' => Str::slug($name.'-'.fake()->unique()->numberBetween(1, 9999)),
            'type' => fake()->randomElement(['public', 'private']),
            'business_hours' => fake()->optional()->randomElement([
                '24/7',
                'Weekdays 09:00–18:00',
                'Extended hours',
            ]),
            'outgoing_email' => fake()->optional()->safeEmail(),
            'department_status_id' => DepartmentStatus::query()->inRandomOrder()->value('id'),
            'signature' => fake()->optional()->randomElement([
                '<p>Best regards,<br>Support Team</p>',
                '<p>Kind regards,<br>Customer Care</p>',
            ]),
            'is_default' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => [
            'type' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn () => [
            'type' => 'private',
        ]);
    }
}
