<?php

namespace Tests\Unit\Admin\AgentStatuses;

use App\Enums\Admin\AgentStatusEndReason;
use App\Enums\Admin\AgentStatusSource;
use App\Enums\Admin\AgentWorkChannel;
use App\Models\Admin\AgentStatus;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\AgentStatuses\AgentStatusResolver;
use App\Services\Admin\AgentStatuses\AgentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AgentStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private function agent(): User
    {
        $role = Role::firstOrCreate(['name' => 'agent'], ['label' => 'Agent', 'type' => 'agent']);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    private function default(): AgentStatus
    {
        return AgentStatus::factory()->default()->create(['slug' => 'available']);
    }

    public function test_it_sets_and_replaces_a_global_status(): void
    {
        $agent = $this->agent();
        $default = $this->default();
        $busy = AgentStatus::factory()->limited()->fallback()->create();
        $service = app(AgentStatusService::class);
        $first = $service->setGlobalStatus($agent, $default, $agent, AgentStatusSource::Self);
        $second = $service->setGlobalStatus($agent, $busy, $agent, AgentStatusSource::Self, 30);
        $this->assertEquals(AgentStatusEndReason::Replaced, $first->fresh()->end_reason);
        $this->assertNotNull($second->expires_at);
        $this->assertSame($default->id, $second->revert_to_status_id);
        $this->assertSame(1, $agent->statusPeriods()->open()->count());
    }

    public function test_identical_transition_is_idempotent(): void
    {
        $agent = $this->agent();
        $status = $this->default();
        $service = app(AgentStatusService::class);
        $first = $service->setGlobalStatus($agent, $status, $agent, AgentStatusSource::Self);
        $second = $service->setGlobalStatus($agent, $status, $agent, AgentStatusSource::Self);
        $this->assertTrue($first->is($second));
        $this->assertSame(1, $agent->statusPeriods()->count());
    }

    public function test_self_service_rejects_non_selectable_status(): void
    {
        $agent = $this->agent();
        $this->default();
        $status = AgentStatus::factory()->selectable(false)->create();
        $this->expectException(ValidationException::class);
        app(AgentStatusService::class)->setGlobalStatus($agent, $status, $agent, AgentStatusSource::Self);
    }

    public function test_channel_cannot_raise_global_availability(): void
    {
        $agent = $this->agent();
        $this->default();
        $away = AgentStatus::factory()->unavailable()->blocked()->create();
        $available = AgentStatus::factory()->available()->eligible()->create();
        $service = app(AgentStatusService::class);
        $service->setGlobalStatus($agent, $away);
        $service->setChannelStatus($agent, $available, AgentWorkChannel::Email);
        $resolved = app(AgentStatusResolver::class)->currentStatus($agent, AgentWorkChannel::Email);
        $this->assertSame('unavailable', $resolved->availability->value);
        $this->assertSame('blocked',$resolved->routingEligibility->value);
    }
}
