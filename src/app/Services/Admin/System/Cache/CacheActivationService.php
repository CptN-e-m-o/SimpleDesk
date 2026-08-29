<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheActivationResultData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CacheActivationService
{
    public function __construct(
        private readonly CacheDriverRegistry $registry,
        private readonly CacheDriverHealthService $health,
        private readonly CacheDeploymentTargetService $deployment,
        private readonly QueueWorkerRestartService $restart,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function activate(
        CacheDriverConfiguration $configuration,
        User $actor,
        bool $force = false,
    ): CacheActivationResultData {
        $observedState = $this->currentRuntimeState();

        if (
            $observedState['mode'] === CacheConfigurationMode::Managed->value
            && $observedState['active_configuration_id'] === $configuration->id
        ) {
            throw ValidationException::withMessages([
                'configuration' => 'This Cache configuration is already active.',
            ]);
        }

        $this->assertActivatable($configuration);

        $targetHealth = $this->health->preflight(
            $configuration,
            $actor,
        );

        $healthOverrideRequired =
            $targetHealth->status !== CacheHealthStatus::Healthy;

        if ($healthOverrideRequired && ! $force) {
            $this->rejectUnhealthyManagedTarget($targetHealth);
        }

        $this->activateManagedConfiguration(
            configurationId: $configuration->id,
            actor: $actor,
            force: $force,
            healthOverrideRequired: $healthOverrideRequired,
            targetHealth: $targetHealth->toArray(),
            observedState: $observedState,
        );

        $restartSignaled = $this->signalRestart(
            actor: $actor,
            configurationId: $configuration->id,
            runtimeMode: CacheConfigurationMode::Managed->value,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        return new CacheActivationResultData(
            settings: $settings,
            forceRequested: $force,
            healthOverrideUsed: $healthOverrideRequired && $force,
            restartSignaled: $restartSignaled,
        );
    }

    public function activateDeployment(
        User $actor,
        bool $force = false,
    ): CacheActivationResultData {
        $observedState = $this->currentRuntimeState();

        if ($observedState['mode'] === CacheConfigurationMode::Deployment->value) {
            throw ValidationException::withMessages([
                'activation' => 'Deployment Cache configuration is already active.',
            ]);
        }

        $target = $this->deployment->resolve();
        $targetHealth = $this->deployment->test($target);

        $this->audit->log(
            area: 'cache_driver_configurations',
            action: 'deployment_preflight',
            subjectType: CacheDriverSettings::class,
            subjectId: CacheDriverSettings::SINGLETON_ID,
            before: null,
            after: null,
            metadata: [
                'target' => [
                    'store' => $target['store'],
                    'driver' => $target['driver'],
                ],
                'health' => $targetHealth->toArray(),
            ],
            actor: $actor,
        );

        $healthOverrideRequired =
            $targetHealth->status !== CacheHealthStatus::Healthy;

        if ($healthOverrideRequired && ! $force) {
            $this->rejectUnhealthyDeploymentTarget($targetHealth);
        }

        $this->activateDeploymentConfiguration(
            actor: $actor,
            force: $force,
            healthOverrideRequired: $healthOverrideRequired,
            target: $target,
            targetHealth: $targetHealth->toArray(),
            observedState: $observedState,
        );

        $restartSignaled = $this->signalRestart(
            actor: $actor,
            configurationId: $observedState['active_configuration_id'],
            runtimeMode: CacheConfigurationMode::Deployment->value,
        );

        $settings = CacheDriverSettings::query()->findOrFail(
            CacheDriverSettings::SINGLETON_ID,
        );

        return new CacheActivationResultData(
            settings: $settings,
            forceRequested: $force,
            healthOverrideUsed: $healthOverrideRequired && $force,
            restartSignaled: $restartSignaled,
        );
    }

    private function activateManagedConfiguration(
        int $configurationId,
        User $actor,
        bool $force,
        bool $healthOverrideRequired,
        array $targetHealth,
        array $observedState,
    ): void {
        DB::transaction(function () use (
            $configurationId,
            $actor,
            $force,
            $healthOverrideRequired,
            $targetHealth,
            $observedState,
        ): void {
            $settings = $this->lockSettings();

            $this->assertRuntimeStateUnchanged(
                $settings,
                $observedState,
            );

            $configuration = CacheDriverConfiguration::withTrashed()
                ->whereKey($configurationId)
                ->lockForUpdate()
                ->first();

            if (! $configuration) {
                throw ValidationException::withMessages([
                    'configuration' => 'The Cache configuration no longer exists.',
                ]);
            }

            if (
                $settings->mode === CacheConfigurationMode::Managed
                && $settings->active_configuration_id === $configuration->id
            ) {
                throw ValidationException::withMessages([
                    'configuration' => 'This Cache configuration is already active.',
                ]);
            }

            $this->assertActivatable($configuration);

            $before = $this->settingsState($settings);

            $settings->update([
                'mode' => CacheConfigurationMode::Managed,
                'active_configuration_id' => $configuration->id,
                'activated_at' => now(),
                'activated_by' => $actor->id,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'cache_driver_configurations',
                action: $force ? 'force_activate' : 'activate',
                subjectType: CacheDriverConfiguration::class,
                subjectId: $configuration->id,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'force_requested' => $force,
                    'target_health_override_used' => $healthOverrideRequired && $force,
                    'target_health' => $targetHealth,
                    'source_ownership' => $observedState['mode'],
                    'target_ownership' => CacheConfigurationMode::Managed->value,
                ],
                actor: $actor,
            );
        });
    }

    private function activateDeploymentConfiguration(
        User $actor,
        bool $force,
        bool $healthOverrideRequired,
        array $target,
        array $targetHealth,
        array $observedState,
    ): void {
        DB::transaction(function () use (
            $actor,
            $force,
            $healthOverrideRequired,
            $target,
            $targetHealth,
            $observedState,
        ): void {
            $settings = $this->lockSettings();

            $this->assertRuntimeStateUnchanged(
                $settings,
                $observedState,
            );

            if ($settings->mode === CacheConfigurationMode::Deployment) {
                throw ValidationException::withMessages([
                    'activation' => 'Deployment Cache configuration is already active.',
                ]);
            }

            $before = $this->settingsState($settings);
            $previousConfigurationId = $settings->active_configuration_id;

            $settings->update([
                'mode' => CacheConfigurationMode::Deployment,
                'active_configuration_id' => null,
                'activated_at' => now(),
                'activated_by' => $actor->id,
            ]);

            $settings->refresh();

            $this->audit->log(
                area: 'cache_driver_configurations',
                action: $force
                    ? 'force_activate_deployment'
                    : 'activate_deployment',
                subjectType: CacheDriverSettings::class,
                subjectId: CacheDriverSettings::SINGLETON_ID,
                before: $before,
                after: $this->settingsState($settings),
                metadata: [
                    'force_requested' => $force,
                    'target_health_override_used' => $healthOverrideRequired && $force,
                    'target_health' => $targetHealth,
                    'source_ownership' => $observedState['mode'],
                    'target_ownership' => CacheConfigurationMode::Deployment->value,
                    'previous_configuration_id' => $previousConfigurationId,
                    'deployment_target' => [
                        'store' => $target['store'],
                        'driver' => $target['driver'],
                    ],
                ],
                actor: $actor,
            );
        });
    }

    private function assertActivatable(
        CacheDriverConfiguration $configuration,
    ): void {
        if ($configuration->trashed()) {
            throw ValidationException::withMessages([
                'configuration' => 'Archived Cache configurations cannot be activated.',
            ]);
        }

        if (! $configuration->is_enabled) {
            throw ValidationException::withMessages([
                'configuration' => 'Disabled Cache configurations cannot be activated.',
            ]);
        }

        $this->validateRuntimeConfiguration($configuration);
    }

    private function validateRuntimeConfiguration(
        CacheDriverConfiguration $configuration,
    ): void {
        try {
            $this->registry
                ->adapter($configuration->driver)
                ->runtimeConfiguration($configuration);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'configuration' => 'The Cache configuration cannot be activated because its runtime configuration is invalid.',
            ]);
        }
    }

    private function lockSettings(): CacheDriverSettings
    {
        $now = now();

        CacheDriverSettings::query()->insertOrIgnore([
            'id' => CacheDriverSettings::SINGLETON_ID,
            'mode' => CacheConfigurationMode::Deployment->value,
            'active_configuration_id' => null,
            'activated_at' => null,
            'activated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return CacheDriverSettings::query()
            ->whereKey(CacheDriverSettings::SINGLETON_ID)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function currentRuntimeState(): array
    {
        $settings = CacheDriverSettings::query()->find(
            CacheDriverSettings::SINGLETON_ID,
        );

        return [
            'mode' => $settings?->mode->value
                ?? CacheConfigurationMode::Deployment->value,
            'active_configuration_id' => $settings?->active_configuration_id,
        ];
    }

    private function assertRuntimeStateUnchanged(
        CacheDriverSettings $settings,
        array $observedState,
    ): void {
        if (
            $settings->mode->value !== $observedState['mode']
            || $settings->active_configuration_id !== $observedState['active_configuration_id']
        ) {
            throw ValidationException::withMessages([
                'activation' => 'Cache ownership changed while activation was being prepared. Refresh the page and try again.',
            ]);
        }
    }

    private function rejectUnhealthyManagedTarget(
        CacheHealthResultData $health,
    ): never {
        throw ValidationException::withMessages([
            'activation' => 'Managed Cache activation is blocked because the target Cache backend is not healthy. '.$health->message,
        ]);
    }

    private function rejectUnhealthyDeploymentTarget(
        CacheHealthResultData $health,
    ): never {
        throw ValidationException::withMessages([
            'activation' => 'Deployment Cache activation is blocked because the deployment Cache backend is not healthy. '.$health->message,
        ]);
    }

    private function signalRestart(
        User $actor,
        ?int $configurationId,
        string $runtimeMode,
    ): bool {
        try {
            $this->restart->signal();
            $success = true;
        } catch (Throwable) {
            $success = false;
        }

        $this->audit->log(
            area: 'cache_driver_configurations',
            action: $success
                ? 'worker_restart_signal_succeeded'
                : 'worker_restart_signal_failed',
            subjectType: CacheDriverSettings::class,
            subjectId: CacheDriverSettings::SINGLETON_ID,
            before: null,
            after: null,
            metadata: [
                'configuration_id' => $configurationId,
                'runtime_mode' => $runtimeMode,
                'signal_issued' => $success,
                'workers_restarted' => false,
            ],
            actor: $actor,
        );

        return $success;
    }

    private function settingsState(
        CacheDriverSettings $settings,
    ): array {
        return [
            'mode' => $settings->mode->value,
            'active_configuration_id' => $settings->active_configuration_id,
            'activated_at' => $settings->activated_at?->toIso8601String(),
            'activated_by' => $settings->activated_by,
        ];
    }
}
