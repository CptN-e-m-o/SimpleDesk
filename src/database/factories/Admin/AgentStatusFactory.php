<?php

namespace Database\Factories\Admin;

use App\Enums\Admin\AgentRoutingEligibility;
use App\Enums\Admin\AgentStatusAvailability;
use App\Models\Admin\AgentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AgentStatusFactory extends Factory
{
    protected $model = AgentStatus::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => ucfirst($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999), 'availability' => AgentStatusAvailability::Available, 'routing_eligibility' => AgentRoutingEligibility::Eligible, 'icon' => 'circle-dot', 'color' => '#2563EB', 'is_active' => true, 'is_selectable' => true, 'is_system' => false, 'is_default' => false];
    }

    public function available(): static
    {
        return $this->state(['availability' => 'available']);
    }

    public function limited(): static
    {
        return $this->state(['availability' => 'limited']);
    }

    public function unavailable(): static
    {
        return $this->state(['availability' => 'unavailable']);
    }

    public function eligible(): static
    {
        return $this->state(['routing_eligibility' => 'eligible']);
    }

    public function fallback(): static
    {
        return $this->state(['routing_eligibility' => 'fallback']);
    }

    public function blocked(): static
    {
        return $this->state(['routing_eligibility' => 'blocked']);
    }

    public function system(): static
    {
        return $this->state(['is_system' => true]);
    }

    public function custom(): static
    {
        return $this->state(['is_system' => false]);
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function archived(): static
    {
        return $this->afterCreating(fn (AgentStatus $s) => $s->delete());
    }

    public function default(): static
    {
        return $this->state(['is_default' => true, 'is_active' => true]);
    }

    public function selectable(bool $value = true): static
    {
        return $this->state(['is_selectable' => $value]);
    }

    public function temporary(int $minutes = 30): static
    {
        return $this->state(['default_duration_minutes' => $minutes]);
    }
}
