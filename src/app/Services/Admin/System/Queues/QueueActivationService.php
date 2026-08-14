<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueActivationResultData;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class QueueActivationService
{
    public function __construct(
        private readonly QueueBacklogService $backlog,
        private readonly QueueDriverRegistry $registry,
        private readonly QueuePinnedWorkloadService $pinnedWorkloads,
        private readonly QueueWorkerRestartService $restart,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function activate(
        QueueDriverConfiguration $configuration,
        User $actor,
        bool $force = false,
    ): QueueActivationResultData {
        $observedState = $this->currentRuntimeState();

        if (
            $observedState['mode'] === QueueConfigurationMode::Managed->value
            && $observedState['active_configuration_id'] === $configuration->id
        ) {
            throw ValidationException::withMessages([
                'configuration' => 'This Queue configuration is already active.',
            ]);
        }

        $pinnedWorkloads = $this->pinnedWorkloads->enabled();
        $workloadRoutingOverrideRequired = $pinnedWorkloads !== [];

        if ($workloadRoutingOverrideRequired && ! $force) {
            $this->rejectPinnedWorkloads($pinnedWorkloads);
        }

        $backlog = $this->backlog->inspect();
        $backlogOverrideRequired = $this->backlogOverrideRequired($backlog);

        if ($backlogOverrideRequired && ! $force) {
            $this->rejectUnsafeBacklog($backlog);
        }

        $this->activateConfiguration(
            configurationId: $configuration->id,
            actor: $actor,
            force: $force,
            backlogOverrideRequired: $backlogOverrideRequired,
            workloadRoutingOverrideRequired: $workloadRoutingOverrideRequired,
            pinnedWorkloads: $pinnedWorkloads,
            backlog: $backlog,
            observedState: $observedState,
        );

        $restartSignaled = $this->signalRestart(
            configurationId: $configuration->id,
            actor: $actor,
        );

        $settings = QueueDriverSettings::query()->findOrFail(
            QueueDriverSettings::SINGLETON_ID,
        );

        return new QueueActivationResultData(
            settings: $settings,
            backlog: $backlog,
            forceRequested: $force,
            backlogOverrideUsed: $backlogOverrideRequired && $force,
            workloadRoutingOverrideUsed: $workloadRoutingOverrideRequired && $force,
            pinnedWorkloads: $pinnedWorkloads,
            restartSignaled: $restartSignaled,
        );
    }

    public function activateDeployment(
        User $actor,
        bool $force = false,
    ): QueueActivationResultData {
        $observedState = $this->currentRuntimeState();

        if ($observedState['mode'] === QueueConfigurationMode::Deployment->value) {
            throw ValidationException::withMessages([
                'activation' => 'Deployment Queue configuration is already active.',
            ]);
        }

        $backlog = $this->backlog->inspect();
        $backlogOverrideRequired = $this->backlogOverrideRequired($backlog);

        if ($backlogOverrideRequired && ! $force) {
            $this->rejectUnsafeBacklog($backlog);
        }

        $previousConfigurationId = $observedState['active_configuration_id'];

        $this->activateDeploymentConfiguration(
            actor: $actor,
            force: $force,
            backlogOverrideRequired: $backlogOverrideRequired,
            backlog: $backlog,
            observedState: $observedState,
        );

        $restartSignaled = $this->signalDeploymentRestart(
            previousConfigurationId: $previousConfigurationId,
            actor: $actor,
        );

        $settings = QueueDriverSettings::query()->findOrFail(
            QueueDriverSettings::SINGLETON_ID,
        );

        return new QueueActivationResultData(
            settings: $settings,
            backlog: $backlog,
            forceRequested: $force,
            backlogOverrideUsed: $backlogOverrideRequired && $force,
            workloadRoutingOverrideUsed: false,
            pinnedWorkloads: [],
            restartSignaled: $restartSignaled,
        );
    }

    private function activateConfiguration(
        int $configurationId,
        User $actor,
        bool $force,
        bool $backlogOverrideRequired,
        bool $workloadRoutingOverrideRequired,
        array $pinnedWorkloads,
        array $backlog,
        array $observedState,
    ): void {
        DB::transaction(function () use (
            $configurationId,
            $actor,
            $force,
            $backlogOverrideRequired,
            $workloadRoutingOverrideRequired,
            $pinnedWorkloads,
            $backlog,
            $observedState,
        ): void {
            $settings = $this->lockSettings();

            $this->assertRuntimeStateUnchanged(
                $settings,
                $observedState,
            );

            $configuration = QueueDriverConfiguration::withTrashed()
                ->whereKey($configurationId)
                ->lockForUpdate()
                ->first();

            if (! $configuration) {
                throw ValidationException::withMessages([
                    'configuration' => 'The Queue configuration no longer exists.',
                ]);
            }

            if ($configuration->trashed()) {
                throw ValidationException::withMessages([
                    'configuration' => 'Archived Queue configurations cannot be activated.',
                ]);
            }

            if (! $configuration->is_enabled) {
                throw ValidationException::withMessages([
                    'configuration' => 'Disabled Queue configurations cannot be activated.',
                ]);
            }

            if (
                $settings->mode === QueueConfigurationMode::Managed
                && $settings->active_configuration_id === $configuration->id
            ) {
                throw ValidationException::withMessages([
                    'configuration' => 'This Queue configuration is already active.',
                ]);
            }

            $this->validateRuntimeConfiguration($configuration);

            $before = $this->settingsState($settings);

            $settings->update([
                'mode' => QueueConfigurationMode::Managed,
                'active_configuration_id' => $configuration->id,
                'worker_restart_required' => true,
                'activated_at' => now(),
                'activated_by' => $actor->id,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'queue_driver_configurations',
                action: 'activate',
                subjectType: QueueDriverConfiguration::class,
                subjectId: $configuration->id,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'force_requested' => $force,
                    'backlog_override_used' => $backlogOverrideRequired && $force,
                    'workload_routing_override_used' => $workloadRoutingOverrideRequired && $force,
                    'pinned_workloads' => $pinnedWorkloads,
                    'backlog' => $this->backlogAuditState($backlog),
                ],
                actor: $actor,
            );
        });
    }

    private function activateDeploymentConfiguration(
        User $actor,
        bool $force,
        bool $backlogOverrideRequired,
        array $backlog,
        array $observedState,
    ): void {
        DB::transaction(function () use (
            $actor,
            $force,
            $backlogOverrideRequired,
            $backlog,
            $observedState,
        ): void {
            $settings = $this->lockSettings();

            $this->assertRuntimeStateUnchanged(
                $settings,
                $observedState,
            );

            if ($settings->mode === QueueConfigurationMode::Deployment) {
                throw ValidationException::withMessages([
                    'activation' => 'Deployment Queue configuration is already active.',
                ]);
            }

            $before = $this->settingsState($settings);
            $previousConfigurationId = $settings->active_configuration_id;

            $settings->update([
                'mode' => QueueConfigurationMode::Deployment,
                'active_configuration_id' => null,
                'worker_restart_required' => true,
                'activated_at' => now(),
                'activated_by' => $actor->id,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'queue_driver_configurations',
                action: 'activate_deployment',
                subjectType: QueueDriverConfiguration::class,
                subjectId: $previousConfigurationId,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'force_requested' => $force,
                    'backlog_override_used' => $backlogOverrideRequired && $force,
                    'backlog' => $this->backlogAuditState($backlog),
                ],
                actor: $actor,
            );
        });
    }

    private function lockSettings(): QueueDriverSettings
    {
        $now = now();

        QueueDriverSettings::query()->insertOrIgnore([
            'id' => QueueDriverSettings::SINGLETON_ID,
            'mode' => QueueConfigurationMode::Deployment->value,
            'active_configuration_id' => null,
            'worker_restart_required' => false,
            'activated_at' => null,
            'activated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return QueueDriverSettings::query()
            ->whereKey(QueueDriverSettings::SINGLETON_ID)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function validateRuntimeConfiguration(
        QueueDriverConfiguration $configuration,
    ): void {
        try {
            $this->registry
                ->adapter($configuration->driver)
                ->runtimeConfiguration($configuration);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'configuration' => 'The Queue configuration cannot be activated because its runtime configuration is invalid.',
            ]);
        }
    }

    private function signalRestart(
        int $configurationId,
        User $actor,
    ): bool {
        try {
            $this->restart->signal();

            $this->markManagedRestartSignaled(
                configurationId: $configurationId,
                actor: $actor,
            );

            return true;
        } catch (Throwable) {
            $this->recordRestartSignalFailure(
                configurationId: $configurationId,
                actor: $actor,
                deployment: false,
            );

            return false;
        }
    }

    private function signalDeploymentRestart(
        ?int $previousConfigurationId,
        User $actor,
    ): bool {
        try {
            $this->restart->signal();

            $this->markDeploymentRestartSignaled(
                previousConfigurationId: $previousConfigurationId,
                actor: $actor,
            );

            return true;
        } catch (Throwable) {
            $this->recordRestartSignalFailure(
                configurationId: $previousConfigurationId,
                actor: $actor,
                deployment: true,
            );

            return false;
        }
    }

    private function markManagedRestartSignaled(
        int $configurationId,
        User $actor,
    ): void {
        DB::transaction(function () use ($configurationId, $actor): void {
            $settings = QueueDriverSettings::query()
                ->whereKey(QueueDriverSettings::SINGLETON_ID)
                ->lockForUpdate()
                ->first();

            if (! $settings) {
                return;
            }

            if (
                $settings->mode !== QueueConfigurationMode::Managed
                || $settings->active_configuration_id !== $configurationId
            ) {
                $this->audit->log(
                    area: 'queue_driver_configurations',
                    action: 'worker_restart_signaled',
                    subjectType: QueueDriverConfiguration::class,
                    subjectId: $configurationId,
                    before: null,
                    after: null,
                    metadata: [
                        'superseded' => true,
                        'runtime_mode' => 'managed',
                    ],
                    actor: $actor,
                );

                return;
            }

            $before = $this->settingsState($settings);

            $settings->update([
                'worker_restart_required' => false,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'queue_driver_configurations',
                action: 'worker_restart_signaled',
                subjectType: QueueDriverConfiguration::class,
                subjectId: $configurationId,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'superseded' => false,
                    'runtime_mode' => 'managed',
                ],
                actor: $actor,
            );
        });
    }

    private function markDeploymentRestartSignaled(
        ?int $previousConfigurationId,
        User $actor,
    ): void {
        DB::transaction(function () use ($previousConfigurationId, $actor): void {
            $settings = QueueDriverSettings::query()
                ->whereKey(QueueDriverSettings::SINGLETON_ID)
                ->lockForUpdate()
                ->first();

            if (! $settings) {
                return;
            }

            if ($settings->mode !== QueueConfigurationMode::Deployment) {
                $this->audit->log(
                    area: 'queue_driver_configurations',
                    action: 'worker_restart_signaled',
                    subjectType: QueueDriverConfiguration::class,
                    subjectId: $previousConfigurationId,
                    before: null,
                    after: null,
                    metadata: [
                        'superseded' => true,
                        'runtime_mode' => 'deployment',
                    ],
                    actor: $actor,
                );

                return;
            }

            $before = $this->settingsState($settings);

            $settings->update([
                'worker_restart_required' => false,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'queue_driver_configurations',
                action: 'worker_restart_signaled',
                subjectType: QueueDriverConfiguration::class,
                subjectId: $previousConfigurationId,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'superseded' => false,
                    'runtime_mode' => 'deployment',
                ],
                actor: $actor,
            );
        });
    }

    private function recordRestartSignalFailure(
        ?int $configurationId,
        User $actor,
        bool $deployment,
    ): void {
        try {
            $settings = QueueDriverSettings::query()->find(
                QueueDriverSettings::SINGLETON_ID,
            );

            $this->audit->log(
                area: 'queue_driver_configurations',
                action: 'worker_restart_signal_failed',
                subjectType: QueueDriverConfiguration::class,
                subjectId: $configurationId,
                before: null,
                after: $settings
                    ? $this->settingsState($settings)
                    : null,
                metadata: [
                    'runtime_mode' => $deployment
                        ? 'deployment'
                        : 'managed',
                ],
                actor: $actor,
            );
        } catch (Throwable) {
        }
    }

    private function currentRuntimeState(): array
    {
        $settings = QueueDriverSettings::query()->find(
            QueueDriverSettings::SINGLETON_ID,
        );

        return [
            'mode' => $settings?->mode->value
                ?? QueueConfigurationMode::Deployment->value,
            'active_configuration_id' => $settings?->active_configuration_id,
        ];
    }

    private function assertRuntimeStateUnchanged(
        QueueDriverSettings $settings,
        array $observedState,
    ): void {
        if (
            $settings->mode->value !== $observedState['mode']
            || $settings->active_configuration_id !== $observedState['active_configuration_id']
        ) {
            throw ValidationException::withMessages([
                'activation' => 'The Queue runtime changed while activation was being prepared. Refresh the page and try again.',
            ]);
        }
    }

    private function rejectPinnedWorkloads(array $workloads): never
    {
        $routes = array_map(
            fn (array $workload): string => "{$workload['label']} → {$workload['connection']}",
            $workloads,
        );

        throw ValidationException::withMessages([
            'activation' => 'Managed Queue activation is blocked because enabled workloads use explicit Queue connections: '.implode(', ', $routes).'. Clear the explicit workload Queue connections or use emergency force activation.',
        ]);
    }

    private function backlogOverrideRequired(array $backlog): bool
    {
        if (! ($backlog['is_complete'] ?? false)) {
            return true;
        }

        return ($backlog['total_pending'] ?? null) !== 0;
    }

    private function rejectUnsafeBacklog(array $backlog): never
    {
        if (! ($backlog['is_complete'] ?? false)) {
            throw ValidationException::withMessages([
                'activation' => 'Queue activation is blocked because the current backlog could not be inspected completely.',
            ]);
        }

        $pending = (int) ($backlog['total_pending'] ?? 0);

        throw ValidationException::withMessages([
            'activation' => "Queue activation is blocked because the current backend still contains {$pending} pending job(s).",
        ]);
    }

    private function backlogAuditState(array $backlog): array
    {
        return [
            'total_pending' => $backlog['total_pending'] ?? null,
            'inspected_pending' => (int) ($backlog['inspected_pending'] ?? 0),
            'is_complete' => (bool) ($backlog['is_complete'] ?? false),
            'has_errors' => (bool) ($backlog['has_errors'] ?? false),
            'inspected_at' => $backlog['inspected_at'] ?? null,
        ];
    }

    private function settingsState(QueueDriverSettings $settings): array
    {
        return [
            'mode' => $settings->mode->value,
            'active_configuration_id' => $settings->active_configuration_id,
            'worker_restart_required' => $settings->worker_restart_required,
            'activated_at' => $settings->activated_at?->toIso8601String(),
            'activated_by' => $settings->activated_by,
        ];
    }
}
