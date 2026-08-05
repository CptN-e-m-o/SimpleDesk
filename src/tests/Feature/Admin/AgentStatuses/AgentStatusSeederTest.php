<?php

namespace Tests\Feature\Admin\AgentStatuses;

use App\Models\Admin\AgentStatus;
use Database\Seeders\AgentStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentStatusSeederTest extends TestCase
{
    use RefreshDatabase;
    public function test_seeder_is_idempotent_and_has_one_default(): void { $this->seed(AgentStatusSeeder::class); $this->seed(AgentStatusSeeder::class); $this->assertSame(9,AgentStatus::count()); $this->assertSame(1,AgentStatus::active()->where('is_default',true)->count()); $this->assertDatabaseHas('agent_statuses',['slug'=>'do-not-disturb','is_system'=>true]); }
}
