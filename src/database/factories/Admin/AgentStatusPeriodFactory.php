<?php

namespace Database\Factories\Admin;

use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgentStatusPeriodFactory extends Factory
{
    protected $model = AgentStatusPeriod::class;
    public function definition(): array { return ['user_id' => User::factory(), 'agent_status_id' => AgentStatus::factory(), 'scope' => 'global', 'started_at' => now()->subHour(), 'source' => 'admin']; }
    public function currentGlobal(): static { return $this->state(['scope' => 'global', 'channel' => null, 'ended_at' => null]); }
    public function currentChannel(string $channel = 'email'): static { return $this->state(['scope' => 'channel', 'channel' => $channel, 'ended_at' => null]); }
    public function historical(): static { return $this->state(['ended_at' => now(), 'end_reason' => 'replaced']); }
    public function expiredOpen(): static { return $this->state(['ended_at' => null, 'expires_at' => now()->subMinute()]); }
    public function futureExpiry(): static { return $this->state(['expires_at' => now()->addHour()]); }
    public function selfSet(): static { return $this->state(['source' => 'self']); }
    public function adminSet(): static { return $this->state(['source' => 'admin']); }
    public function systemSet(): static { return $this->state(['source' => 'system']); }
    public function withRevert(AgentStatus|int $status): static { return $this->state(['revert_to_status_id' => $status instanceof AgentStatus ? $status->id : $status]); }
    public function ended(string $reason): static { return $this->state(['ended_at' => now(), 'end_reason' => $reason]); }
}
