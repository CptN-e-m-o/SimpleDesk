<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\BroadcastHealthStatus;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Broadcasting\BroadcastActivationService;
use App\Services\Admin\System\Broadcasting\BroadcastDriverHealthService;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class BroadcastActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_activation_commits_ownership_signals_restart_and_does_not_mutate_current_runtime(): void
    {
        [$user, $configuration] = $this->configuration();
        config()->set('broadcasting.default', 'log');
        $this->health(BroadcastHealthStatus::Healthy);
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->once();
        $this->app->instance(QueueWorkerRestartService::class, $restart);
        $result = app(BroadcastActivationService::class)->activate($configuration, $user);
        $this->assertSame(BroadcastConfigurationMode::Managed, $result->settings->mode);
        $this->assertSame($configuration->id, $result->settings->active_configuration_id);
        $this->assertTrue($result->restartSignaled);
        $this->assertSame('log', config('broadcasting.default'));
    }

    public function test_unhealthy_normal_activation_is_blocked_but_force_can_override_operation_only(): void
    {
        [$user, $configuration] = $this->configuration();
        $this->health(BroadcastHealthStatus::Unhealthy);
        try {
            app(BroadcastActivationService::class)->activate($configuration, $user);
            $this->fail('Normal activation should be blocked.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('broadcast_driver_settings', ['mode' => 'managed']);
        }
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->once();
        $this->app->instance(QueueWorkerRestartService::class, $restart);
        $result = app(BroadcastActivationService::class)->activate($configuration, $user, true);
        $this->assertTrue($result->healthOverrideUsed);
        $this->assertDatabaseHas('broadcast_driver_settings', ['mode' => 'managed', 'active_configuration_id' => $configuration->id]);
    }

    public function test_force_cannot_activate_disabled_structurally_invalid_profile(): void
    {
        [$user, $configuration] = $this->configuration();
        $configuration->update(['is_enabled' => false]);
        $this->expectException(ValidationException::class);
        app(BroadcastActivationService::class)->activate($configuration->refresh(), $user, true);
    }

    public function test_restart_failure_leaves_committed_ownership_and_returns_warning_state(): void
    {
        [$user, $configuration] = $this->configuration();
        $this->health(BroadcastHealthStatus::Healthy);
        $restart = Mockery::mock(QueueWorkerRestartService::class);
        $restart->shouldReceive('signal')->once()->andThrow(new \RuntimeException('restart failed'));
        $this->app->instance(QueueWorkerRestartService::class, $restart);
        $result = app(BroadcastActivationService::class)->activate($configuration, $user);
        $this->assertFalse($result->restartSignaled);
        $this->assertDatabaseHas('broadcast_driver_settings', ['mode' => 'managed', 'active_configuration_id' => $configuration->id]);
        $this->assertDatabaseHas('system_audit_logs', ['area' => 'broadcast_driver_configurations', 'action' => 'worker_restart_signal_failed']);
    }

    private function health(BroadcastHealthStatus $status): void
    {
        $health = Mockery::mock(BroadcastDriverHealthService::class);
        $health->shouldReceive('preflight')->andReturn(new BroadcastHealthResultData($status, 2, 'probe result'));
        $this->app->instance(BroadcastDriverHealthService::class, $health);
    }

    private function configuration(): array
    {
        $user = User::factory()->create();
        $connection = InfrastructureConnection::query()->create(['name' => 'Pusher', 'type' => 'pusher', 'source' => 'managed', 'configuration' => ['app_id' => 'app', 'host' => '', 'port' => 443, 'scheme' => 'https', 'cluster' => 'eu', 'public_host' => '', 'public_port' => null, 'public_scheme' => ''], 'credentials' => ['app_key' => 'key', 'app_secret' => 'secret'], 'is_enabled' => true, 'created_by' => $user->id, 'updated_by' => $user->id]);
        $configuration = BroadcastDriverConfiguration::query()->create(['name' => 'Pusher', 'driver' => 'pusher', 'infrastructure_connection_id' => $connection->id, 'configuration' => [], 'is_enabled' => true, 'created_by' => $user->id, 'updated_by' => $user->id]);

        return [$user, $configuration];
    }
}
