<?php

namespace App\Services\Admin\System\Storage;

use App\Data\Admin\System\Storage\StorageActivationResultData;
use App\Enums\Admin\System\StorageConfigurationMode;
use App\Enums\Admin\System\StorageHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorageActivationService
{
    public function __construct(private readonly StorageDriverRegistry $registry, private readonly StorageDriverHealthService $health, private readonly StorageDeploymentTargetService $deployment, private readonly QueueWorkerRestartService $restart, private readonly SystemAuditLogger $audit, private readonly StorageRuntimeFingerprintService $fingerprints) {}

    public function activate(StorageDriverConfiguration $configuration, User $actor, bool $force = false): StorageActivationResultData
    {
        $observed = $this->state();
        $target = StorageDriverConfiguration::withTrashed()->findOrFail($configuration->id);
        $this->assertActivatable($target);
        $runtime = $this->runtimeFingerprints($target);
        $health = $this->health->preflight($target, $actor);
        $override = $health->status !== StorageHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Managed Storage activation requires a healthy provider preflight. '.$health->message]);
        }
        DB::transaction(function () use ($configuration, $actor, $force, $health, $override, $observed, $runtime) {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $target = StorageDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($configuration->id);
            $infrastructure = $this->lockInfrastructure($target);
            if ($this->fingerprints->target($target) !== $runtime['target'] || ($infrastructure ? $this->fingerprints->infrastructure($infrastructure) : null) !== $runtime['infrastructure']) {
                throw ValidationException::withMessages(['activation' => 'Storage runtime configuration changed while activation was prepared.']);
            }
            $this->assertActivatable($target);
            $before = $this->settingsState($settings);
            $settings->update(['mode' => StorageConfigurationMode::Managed, 'active_configuration_id' => $target->id, 'activated_by' => $actor->id, 'activated_at' => now()]);
            $this->audit->log('storage_driver_configurations', $force ? 'force_activate' : 'activate', StorageDriverConfiguration::class, $target->id, $before, $this->settingsState($settings->refresh()), ['target_health' => $health->toArray(), 'target_health_override_used' => $force && $override], $actor);
        });

        return $this->restartResult($actor);
    }

    public function activateDeployment(User $actor, bool $force = false): StorageActivationResultData
    {
        $observed = $this->state();
        $target = $this->deployment->resolve();
        $health = $this->deployment->test($target);
        $override = $health->status !== StorageHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Deployment Storage activation preflight failed. '.$health->message]);
        }
        DB::transaction(function () use ($actor, $force, $target, $health, $override, $observed) {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $this->deployment->resolve();
            $before = $this->settingsState($settings);
            $settings->update(['mode' => StorageConfigurationMode::Deployment, 'active_configuration_id' => null, 'activated_by' => $actor->id, 'activated_at' => now()]);
            $this->audit->log('storage_driver_configurations', $force ? 'force_activate_deployment' : 'activate_deployment', StorageDriverSettings::class, 1, $before, $this->settingsState($settings->refresh()), ['deployment_target' => ['disk' => $target['disk'], 'driver' => $target['driver']], 'target_health' => $health->toArray(), 'target_health_override_used' => $force && $override], $actor);
        });

        return $this->restartResult($actor);
    }

    private function restartResult(User $actor): StorageActivationResultData
    {
        try {
            $this->restart->signal();

            return new StorageActivationResultData(true);
        } catch (Throwable) {
            $this->audit->log('storage_driver_configurations', 'restart_signal_failed', StorageDriverSettings::class, 1, null, null, ['signal_issued' => false, 'workers_restarted' => false], $actor);

            return new StorageActivationResultData(false, 'Storage ownership was committed, but queue worker restart signaling failed.');
        }
    }

    private function assertActivatable(StorageDriverConfiguration $configuration): void
    {
        if ($configuration->trashed() || ! $configuration->is_enabled) {
            throw ValidationException::withMessages(['configuration' => 'Archived or disabled Storage configurations cannot be activated.']);
        }
        $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
    }

    private function runtimeFingerprints(StorageDriverConfiguration $configuration): array
    {
        $connection = $this->fingerprints->usesInfrastructure($configuration) && $configuration->infrastructure_connection_id ? InfrastructureConnection::withTrashed()->find($configuration->infrastructure_connection_id) : null;

        return ['target' => $this->fingerprints->target($configuration), 'infrastructure' => $connection ? $this->fingerprints->infrastructure($connection) : null];
    }

    private function lockInfrastructure(StorageDriverConfiguration $configuration): ?InfrastructureConnection
    {
        if (! $this->fingerprints->usesInfrastructure($configuration) || ! $configuration->infrastructure_connection_id) {
            return null;
        }

        return InfrastructureConnection::withTrashed()->whereKey($configuration->infrastructure_connection_id)->lockForUpdate()->first();
    }

    private function state(): array
    {
        $settings = StorageDriverSettings::query()->find(1);

        return ['mode' => $settings?->getRawOriginal('mode') ?? 'deployment', 'active_configuration_id' => $settings?->active_configuration_id];
    }

    private function lockSettings(): StorageDriverSettings
    {
        $now = now();
        StorageDriverSettings::query()->insertOrIgnore(['id' => 1, 'mode' => 'deployment', 'created_at' => $now, 'updated_at' => $now]);

        return StorageDriverSettings::query()->whereKey(1)->lockForUpdate()->firstOrFail();
    }

    private function assertUnchanged(StorageDriverSettings $settings, array $observed): void
    {
        if ($settings->getRawOriginal('mode') !== $observed['mode'] || $settings->active_configuration_id !== $observed['active_configuration_id']) {
            throw ValidationException::withMessages(['activation' => 'Storage ownership changed while activation was prepared.']);
        }
    }

    private function settingsState(StorageDriverSettings $settings): array
    {
        $activatedAt = $settings->getAttribute('activated_at');

        return ['mode' => $settings->getRawOriginal('mode'), 'active_configuration_id' => $settings->active_configuration_id, 'activated_at' => $activatedAt instanceof \DateTimeInterface ? $activatedAt->format(DATE_ATOM) : null, 'activated_by' => $settings->activated_by];
    }
}
