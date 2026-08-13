<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Enums\Admin\System\InfrastructureHealthTrigger;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionHealthService;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Admin\System\FakeInfrastructureConnectionAdapter;
use Tests\TestCase;

class InfrastructureConnectionHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_creates_history_and_latest_entry(): void
    {
        $this->app->instance(InfrastructureConnectionRegistry::class, new InfrastructureConnectionRegistry($this->app, ['redis' => FakeInfrastructureConnectionAdapter::class]));
        $c = InfrastructureConnection::factory()->create();
        $this->app->make(InfrastructureConnectionHealthService::class)->test($c, InfrastructureHealthTrigger::Scheduled);
        $this->assertCount(1, $c->healthChecks);
        $this->assertSame('healthy', $c->latestHealthCheck->status->value);
    }
}
