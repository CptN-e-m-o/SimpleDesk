<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueActivationService;
use App\Services\Admin\System\Queues\QueueBacklogService;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class QueueActivationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_empty_backlog_can_activate_managed_configuration(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        $backlog =
            $this->backlog([
                'queues' => [],
                'total_pending' => 0,
                'inspected_pending' => 0,
                'is_complete' => true,
                'has_errors' => false,
                'inspected_at' => '2026-08-14T00:00:00+00:00',
            ]);

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->once())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activate(
            $configuration,
            $actor,
        );

        $settings = QueueDriverSettings::query()
            ->findOrFail(
                QueueDriverSettings::SINGLETON_ID,
            );

        $this->assertSame(
            QueueConfigurationMode::Managed,
            $settings->mode,
        );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );

        $this->assertFalse(
            $settings->worker_restart_required,
        );

        $this->assertSame(
            $actor->id,
            $settings->activated_by,
        );

        $this->assertNotNull(
            $settings->activated_at,
        );

        $this->assertTrue(
            $result->restartSignaled,
        );

        $this->assertFalse(
            $result->forceRequested,
        );

        $this->assertFalse(
            $result->backlogOverrideUsed,
        );

        $this->assertSame(
            [
                'activation_preflight',
                'activate',
                'worker_restart_signaled',
            ],
            SystemAuditLog::query()
                ->where(
                    'area',
                    'queue_driver_configurations',
                )
                ->orderBy('id')
                ->pluck('action')
                ->all(),
        );
    }

    public function test_pending_jobs_block_normal_activation(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        $backlog =
            $this->backlog([
                'queues' => [],
                'total_pending' => 3,
                'inspected_pending' => 3,
                'is_complete' => true,
                'has_errors' => false,
                'inspected_at' => '2026-08-14T00:00:00+00:00',
            ]);

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Activation should be blocked while pending jobs exist.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );
        }

        $this->assertFalse(
            QueueDriverSettings::query()
                ->exists(),
        );

        $this->assertSame(
            0,
            SystemAuditLog::query()
                ->where(
                    'action',
                    'activate',
                )
                ->count(),
        );
    }

    public function test_incomplete_backlog_blocks_normal_activation(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        $backlog =
            $this->backlog([
                'queues' => [],
                'total_pending' => null,
                'inspected_pending' => 5,
                'is_complete' => false,
                'has_errors' => true,
                'inspected_at' => '2026-08-14T00:00:00+00:00',
            ]);

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Activation should be blocked when backlog inspection is incomplete.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );
        }

        $this->assertFalse(
            QueueDriverSettings::query()
                ->exists(),
        );
    }

    public function test_force_activation_can_override_unsafe_backlog(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        $backlogData = [
            'queues' => [],
            'total_pending' => null,
            'inspected_pending' => 7,
            'is_complete' => false,
            'has_errors' => true,
            'inspected_at' => '2026-08-14T00:00:00+00:00',
        ];

        $backlog =
            $this->backlog(
                $backlogData,
            );

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->once())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activate(
            $configuration,
            $actor,
            true,
        );

        $this->assertTrue(
            $result->forceRequested,
        );

        $this->assertTrue(
            $result->backlogOverrideUsed,
        );

        $settings = QueueDriverSettings::query()
            ->findOrFail(
                QueueDriverSettings::SINGLETON_ID,
            );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );

        $audit = SystemAuditLog::query()
            ->where(
                'action',
                'activate',
            )
            ->firstOrFail();

        $this->assertTrue(
            $audit
                ->metadata[
            'force_requested'
            ],
        );

        $this->assertTrue(
            $audit
                ->metadata[
            'backlog_override_used'
            ],
        );

        $this->assertFalse(
            $audit
                ->metadata[
            'backlog'
            ][
            'is_complete'
            ],
        );

        $this->assertSame(
            7,
            $audit
                ->metadata[
            'backlog'
            ][
            'inspected_pending'
            ],
        );
    }

    public function test_pinned_enabled_workload_blocks_normal_managed_activation(): void
    {
        config()->set('simpledesk-mail-automation.sync.queue_connection', 'redis');
        config()->set('simpledesk-mail-automation.enabled', true);
        config()->set('simpledesk-mail-automation.sync.enabled', true);

        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $backlog = $this->createMock(
            QueueBacklogService::class,
        );

        $backlog
            ->expects($this->never())
            ->method('inspect');

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Managed activation should be blocked when an enabled workload uses an explicit Queue connection.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );

            $this->assertStringContainsString(
                'Mailbox synchronization',
                $exception->errors()['activation'][0],
            );

            $this->assertStringContainsString(
                'redis',
                $exception->errors()['activation'][0],
            );
        }

        $this->assertFalse(
            QueueDriverSettings::query()->exists(),
        );
    }

    public function test_force_activation_can_override_pinned_workload_routing(): void
    {
        config()->set('simpledesk-mail-automation.sync.queue_connection', 'redis');
        config()->set('simpledesk-mail-automation.enabled', true);
        config()->set('simpledesk-mail-automation.sync.enabled', true);

        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $backlog = $this->backlog([
            'queues' => [],
            'total_pending' => 0,
            'inspected_pending' => 0,
            'is_complete' => true,
            'has_errors' => false,
            'inspected_at' => '2026-08-14T00:00:00+00:00',
        ]);

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activate(
            $configuration,
            $actor,
            true,
        );

        $this->assertTrue(
            $result->forceRequested,
        );

        $this->assertFalse(
            $result->backlogOverrideUsed,
        );

        $this->assertTrue(
            $result->workloadRoutingOverrideUsed,
        );

        $this->assertNotEmpty(
            $result->pinnedWorkloads,
        );

        $this->assertSame(
            'mail_sync',
            $result->pinnedWorkloads[0]['key'],
        );

        $this->assertSame(
            'redis',
            $result->pinnedWorkloads[0]['connection'],
        );

        $audit = SystemAuditLog::query()
            ->where('action', 'activate')
            ->firstOrFail();

        $this->assertTrue(
            $audit->metadata['workload_routing_override_used'],
        );

        $this->assertSame(
            'mail_sync',
            $audit->metadata['pinned_workloads'][0]['key'],
        );
    }

    public function test_restart_signal_failure_leaves_restart_required(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        $backlog =
            $this->backlog([
                'queues' => [],
                'total_pending' => 0,
                'inspected_pending' => 0,
                'is_complete' => true,
                'has_errors' => false,
                'inspected_at' => '2026-08-14T00:00:00+00:00',
            ]);

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->once())
            ->method('signal')
            ->willThrowException(
                new RuntimeException(
                    'Restart unavailable.',
                ),
            );

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activate(
            $configuration,
            $actor,
        );

        $settings = QueueDriverSettings::query()
            ->findOrFail(
                QueueDriverSettings::SINGLETON_ID,
            );

        $this->assertFalse(
            $result->restartSignaled,
        );

        $this->assertTrue(
            $settings->worker_restart_required,
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

    public function test_disabled_configuration_cannot_be_activated(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration(
                enabled: false,
            );

        $backlog =
            $this->backlog([
                'queues' => [],
                'total_pending' => 0,
                'inspected_pending' => 0,
                'is_complete' => true,
                'has_errors' => false,
                'inspected_at' => '2026-08-14T00:00:00+00:00',
            ]);

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Disabled Queue configuration should not be activated.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'configuration',
                $exception->errors(),
            );
        }

        $this->assertFalse(
            QueueDriverSettings::query()
                ->exists(),
        );
    }

    public function test_already_active_configuration_is_rejected_without_restart(): void
    {
        $actor = User::factory()->create();

        $configuration =
            $this->configuration();

        QueueDriverSettings::query()
            ->create([
                'id' => QueueDriverSettings::SINGLETON_ID,

                'mode' => QueueConfigurationMode::Managed,

                'active_configuration_id' => $configuration->id,

                'worker_restart_required' => false,

                'activated_at' => now(),

                'activated_by' => $actor->id,
            ]);

        $backlog =
            $this->createMock(
                QueueBacklogService::class,
            );

        $backlog
            ->expects($this->never())
            ->method('inspect');

        $restart =
            $this->createMock(
                QueueWorkerRestartService::class,
            );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activate(
                $configuration,
                $actor,
            );

            $this->fail(
                'Already active Queue configuration should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'configuration',
                $exception->errors(),
            );
        }
    }

    public function test_managed_runtime_can_return_to_deployment_when_backlog_is_empty(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        QueueDriverSettings::query()->create([
            'id' => QueueDriverSettings::SINGLETON_ID,
            'mode' => QueueConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'worker_restart_required' => false,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        $backlog = $this->backlog([
            'queues' => [],
            'total_pending' => 0,
            'inspected_pending' => 0,
            'is_complete' => true,
            'has_errors' => false,
            'inspected_at' => '2026-08-14T00:00:00+00:00',
        ]);

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activateDeployment(
            $actor,
        );

        $settings = QueueDriverSettings::query()->findOrFail(
            QueueDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            QueueConfigurationMode::Deployment,
            $settings->mode,
        );

        $this->assertNull(
            $settings->active_configuration_id,
        );

        $this->assertFalse(
            $settings->worker_restart_required,
        );

        $this->assertTrue(
            $result->restartSignaled,
        );

        $this->assertFalse(
            $result->backlogOverrideUsed,
        );

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where('action', 'activate_deployment')
                ->count(),
        );
    }

    public function test_pending_jobs_block_normal_return_to_deployment(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        QueueDriverSettings::query()->create([
            'id' => QueueDriverSettings::SINGLETON_ID,
            'mode' => QueueConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'worker_restart_required' => false,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        $backlog = $this->backlog([
            'queues' => [],
            'total_pending' => 4,
            'inspected_pending' => 4,
            'is_complete' => true,
            'has_errors' => false,
            'inspected_at' => '2026-08-14T00:00:00+00:00',
        ]);

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activateDeployment(
                $actor,
            );

            $this->fail(
                'Deployment activation should be blocked while pending jobs exist.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );
        }

        $settings = QueueDriverSettings::query()->findOrFail(
            QueueDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            QueueConfigurationMode::Managed,
            $settings->mode,
        );

        $this->assertSame(
            $configuration->id,
            $settings->active_configuration_id,
        );
    }

    public function test_force_return_to_deployment_can_override_unsafe_backlog(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        QueueDriverSettings::query()->create([
            'id' => QueueDriverSettings::SINGLETON_ID,
            'mode' => QueueConfigurationMode::Managed,
            'active_configuration_id' => $configuration->id,
            'worker_restart_required' => false,
            'activated_at' => now(),
            'activated_by' => $actor->id,
        ]);

        $backlog = $this->backlog([
            'queues' => [],
            'total_pending' => null,
            'inspected_pending' => 2,
            'is_complete' => false,
            'has_errors' => true,
            'inspected_at' => '2026-08-14T00:00:00+00:00',
        ]);

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->once())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        $result = $service->activateDeployment(
            $actor,
            true,
        );

        $settings = QueueDriverSettings::query()->findOrFail(
            QueueDriverSettings::SINGLETON_ID,
        );

        $this->assertSame(
            QueueConfigurationMode::Deployment,
            $settings->mode,
        );

        $this->assertNull(
            $settings->active_configuration_id,
        );

        $this->assertTrue(
            $result->forceRequested,
        );

        $this->assertTrue(
            $result->backlogOverrideUsed,
        );

        $audit = SystemAuditLog::query()
            ->where('action', 'activate_deployment')
            ->firstOrFail();

        $this->assertTrue(
            $audit->metadata['force_requested'],
        );

        $this->assertTrue(
            $audit->metadata['backlog_override_used'],
        );
    }

    public function test_deployment_activation_is_rejected_when_deployment_is_already_active(): void
    {
        $actor = User::factory()->create();

        $backlog = $this->createMock(
            QueueBacklogService::class,
        );

        $backlog
            ->expects($this->never())
            ->method('inspect');

        $restart = $this->createMock(
            QueueWorkerRestartService::class,
        );

        $restart
            ->expects($this->never())
            ->method('signal');

        $service = $this->service(
            $backlog,
            $restart,
        );

        try {
            $service->activateDeployment(
                $actor,
            );

            $this->fail(
                'Deployment Queue configuration is already active.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'activation',
                $exception->errors(),
            );
        }
    }

    private function service(
        QueueBacklogService $backlog,
        QueueWorkerRestartService $restart,
    ): QueueActivationService {
        $this->app->instance(
            QueueBacklogService::class,
            $backlog,
        );

        $this->app->instance(
            QueueWorkerRestartService::class,
            $restart,
        );

        return $this->app->make(
            QueueActivationService::class,
        );
    }

    private function backlog(
        array $result,
    ): QueueBacklogService {
        $backlog =
            $this->createMock(
                QueueBacklogService::class,
            );

        $backlog
            ->expects($this->once())
            ->method('inspect')
            ->willReturn(
                $result,
            );

        return $backlog;
    }

    private function configuration(
        bool $enabled = true,
    ): QueueDriverConfiguration {
        return QueueDriverConfiguration::query()
            ->create([
                'name' => 'Activation target',

                'driver' => QueueDriverType::Sync,

                'infrastructure_connection_id' => null,

                'configuration' => [],

                'is_enabled' => $enabled,
            ]);
    }
}
