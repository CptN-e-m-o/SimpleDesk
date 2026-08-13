<?php

namespace Tests\Feature\Admin\AgentStatuses;

use App\Models\Admin\AgentStatus;
use App\Models\Admin\AgentStatusPeriod;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_expires_and_reverts_a_period_idempotently(): void
    {
        $role = Role::firstOrCreate(['name' => 'agent'], ['label' => 'Agent', 'type' => 'agent']);
        $agent = User::factory()->create();
        $agent->roles()->attach($role);
        $default = AgentStatus::factory()->default()->create();
        $temporary = AgentStatus::factory()->temporary()->create();
        AgentStatusPeriod::factory()->for($agent, 'agent')->for($temporary, 'status')->expiredOpen()->withRevert($default)->create();
        $this->artisan('simpledesk:agent-statuses:expire')->assertSuccessful()->expectsOutputToContain('expired: 1');
        $this->artisan('simpledesk:agent-statuses:expire')->assertSuccessful()->expectsOutputToContain('expired: 0');
        $this->assertSame(1, $agent->statusPeriods()->open()->count());
    }
}
