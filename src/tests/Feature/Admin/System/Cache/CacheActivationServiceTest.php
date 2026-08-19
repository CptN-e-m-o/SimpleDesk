<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Cache\CacheActivationService;
use App\Services\Admin\System\Cache\CacheDeploymentTargetService;
use App\Services\Admin\System\Cache\CacheDriverHealthService;
use App\Services\Admin\System\Cache\CacheDriverRegistry;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class CacheActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_healthy_configuration_can_be_activated(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $health = $this->health(
            CacheHealthStatus::Healthy,
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $result = $this->service(
            health: $health,
            restart: $restart,
        )->activate(
            $configuration,
            $actor,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            CacheConfigurationMode::Managed,
            $settings->mode,
        );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );

        $this->assertSame(
            $actor->id,
            $settings->activated_by,
        );

        $this->assertTrue($result->restartSignaled);
        $this->assertFalse($result->forceRequested);
        $this->assertFalse($result->healthOverrideUsed);

        $this->assertSame(
            [
                'activate',
                'worker_restart_signal_succeeded',
            ],
            SystemAuditLog::query()
                ->where(
                    'area',
                    'cache_driver_configurations',
                )
                ->orderBy('id')
                ->pluck('action')
                ->all(),
        );
    }

    public function test_unhealthy_target_blocks_normal_activation(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $health = $this->health(
            CacheHealthStatus::Unhealthy,
            'Cache lock verification failed.',
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        try {
            $this->service(
                health: $health,
                restart: $restart,
            )->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Unhealthy Cache target should block normal activation.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );

            $this->assertStringContainsString(
                'not healthy',
                $exception->errors()['activation'][0],
            );
        }

        $this->assertFalse(
            CacheDriverSettings::query()->exists(),
        );
    }

    public function test_force_activation_can_override_operational_health_failure(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $health = $this->health(
            CacheHealthStatus::Unavailable,
            'Cache target is unavailable.',
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $result = $this->service(
            health: $health,
            restart: $restart,
        )->activate(
            $configuration,
            $actor,
            true,
        );

        $this->assertTrue($result->forceRequested);
        $this->assertTrue($result->healthOverrideUsed);

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );

        $audit = SystemAuditLog::query()
            ->where('action', 'force_activate')
            ->firstOrFail();

        $this->assertTrue(
            $audit->metadata['force_requested'],
        );

        $this->assertTrue(
            $audit->metadata['target_health_override_used'],
        );

        $this->assertSame(
            'unavailable',
            $audit->metadata['target_health']['status'],
        );
    }

    public function test_force_activation_cannot_activate_disabled_configuration(): void
    {
        $actor = User::factory()->create();

        $configuration = $this->configuration(
            enabled: false,
        );

        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $health
            ->expects($this->never())
            ->method('preflight');

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        $this->expectException(
            ValidationException::class,
        );

        $this->service(
            health: $health,
            restart: $restart,
        )->activate(
            $configuration,
            $actor,
            true,
        );
    }

    public function test_restart_signal_failure_keeps_committed_cache_ownership(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $health = $this->health(
            CacheHealthStatus::Healthy,
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal')
            ->willThrowException(
                new RuntimeException(
                    'Restart signal unavailable.',
                ),
            );

        $result = $this->service(
            health: $health,
            restart: $restart,
        )->activate(
            $configuration,
            $actor,
        );

        $this->assertFalse(
            $result->restartSignaled,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            CacheConfigurationMode::Managed,
            $settings->mode,
        );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where(
                    'action',
                    'worker_restart_signal_failed',
                )
                ->count(),
        );
    }

    public function test_runtime_change_during_preflight_rejects_activation(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();
        $other = $this->configuration('Other Cache');

        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $health
            ->expects($this->once())
            ->method('preflight')
            ->willReturnCallback(
                function () use (
                    $other,
                    $actor,
                ): CacheHealthResultData {
                    CacheDriverSettings::query()->create([
                        'id' => CacheDriverSettings::SINGLETON_ID,
                        'mode' => CacheConfigurationMode::Managed,
                        'active_configuration_id' => $other->id,
                        'activated_at' => now(),
                        'activated_by' => $actor->id,
                    ]);

                    return $this->healthResult(
                        CacheHealthStatus::Healthy,
                    );
                },
            );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        try {
            $this->service(
                health: $health,
                restart: $restart,
            )->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Activation should reject a runtime ownership change during preflight.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );
        }

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            $other->id,
            $settings->active_configuration_id,
        );
    }

    public function test_healthy_deployment_target_can_be_activated(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $this->managedSettings(
            $configuration,
            $actor,
        );

        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $deployment = $this->deployment(
            CacheHealthStatus::Healthy,
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $result = $this->service(
            health: $health,
            restart: $restart,
            deployment: $deployment,
        )->activateDeployment(
            $actor,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            CacheConfigurationMode::Deployment,
            $settings->mode,
        );

        $this->assertNull(
            $settings->active_configuration_id,
        );

        $this->assertTrue(
            $result->restartSignaled,
        );

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where(
                    'action',
                    'deployment_preflight',
                )
                ->count(),
        );

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where(
                    'action',
                    'activate_deployment',
                )
                ->count(),
        );
    }

    public function test_unhealthy_deployment_target_blocks_normal_return(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $this->managedSettings(
            $configuration,
            $actor,
        );

        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $deployment = $this->deployment(
            CacheHealthStatus::Unavailable,
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        $this->expectException(
            ValidationException::class,
        );

        $this->service(
            health: $health,
            restart: $restart,
            deployment: $deployment,
        )->activateDeployment(
            $actor,
        );
    }

    public function test_force_return_can_override_operational_deployment_health_failure(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $this->managedSettings(
            $configuration,
            $actor,
        );

        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $deployment = $this->deployment(
            CacheHealthStatus::Unhealthy,
        );

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $result = $this->service(
            health: $health,
            restart: $restart,
            deployment: $deployment,
        )->activateDeployment(
            $actor,
            true,
        );

        $this->assertTrue(
            $result->forceRequested,
        );

        $this->assertTrue(
            $result->healthOverrideUsed,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            CacheConfigurationMode::Deployment,
            $settings->mode,
        );

        $audit = SystemAuditLog::query()
            ->where(
                'action',
                'force_activate_deployment',
            )
            ->firstOrFail();

        $this->assertTrue(
            $audit->metadata['target_health_override_used'],
        );
    }

    private function configuration(
        string $name = 'Managed Cache',
        bool $enabled = true,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => $name,
            'driver' => 'file',
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => $enabled,
        ]);
    }

    private function managedSettings(
        CacheDriverConfiguration $configuration,
        User $actor,
    ): CacheDriverSettings {
        return CacheDriverSettings::query()->create([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);
    }

    private function health(
        CacheHealthStatus $status,
        string $message = 'Cache target verified.',
    ): CacheDriverHealthService {
        $health = $this->createMock(
            CacheDriverHealthService::class,
        );

        $health
            ->method('preflight')
            ->willReturn(
                $this->healthResult(
                    $status,
                    $message,
                ),
            );

        return $health;
    }

    private function deployment(
        CacheHealthStatus $status,
    ): CacheDeploymentTargetService {
        $deployment = $this->createMock(
            CacheDeploymentTargetService::class,
        );

        $target = [
            'store' => 'database',
            'driver' => 'database',
            'configuration' => [
                'driver' => 'database',
            ],
        ];

        $deployment
            ->method('resolve')
            ->willReturn($target);

        $deployment
            ->method('test')
            ->willReturn(
                $this->healthResult(
                    $status,
                    $status === CacheHealthStatus::Healthy
                        ? 'Deployment Cache target verified.'
                        : 'Deployment Cache target unavailable.',
                ),
            );

        return $deployment;
    }

    private function healthResult(
        CacheHealthStatus $status,
        string $message = 'Cache target verified.',
    ): CacheHealthResultData {
        return new CacheHealthResultData(
            status: $status,
            latencyMs: 5,
            message: $message,
        );
    }

    private function service(
        CacheDriverHealthService $health,
        QueueWorkerRestartService $restart,
        ?CacheDeploymentTargetService $deployment = null,
    ): CacheActivationService {
        return new CacheActivationService(
            registry: app(CacheDriverRegistry::class),
            health: $health,
            deployment: $deployment
            ?? $this->createMock(
                CacheDeploymentTargetService::class,
            ),
            restart: $restart,
            audit: app(SystemAuditLogger::class),
        );
    }
}
