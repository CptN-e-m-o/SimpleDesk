<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Data\Admin\System\Broadcasting\BroadcastActivationResultData;
use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\BroadcastHealthStatus;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class BroadcastActivationService
{
    public function __construct(private readonly BroadcastDriverRegistry $registry, private readonly BroadcastDriverHealthService $health, private readonly BroadcastDeploymentTargetService $deployment, private readonly QueueWorkerRestartService $restart, private readonly SystemAuditLogger $audit) {}

    public function activate(BroadcastDriverConfiguration $configuration, User $actor, bool $force = false): BroadcastActivationResultData
    {
        $observed = $this->state();
        $this->assertActivatable($configuration);
        $health = $this->health->preflight($configuration, $actor);
        $override = $health->status !== BroadcastHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Managed Broadcast activation requires a healthy authenticated provider preflight. '.$health->message]);
        }
        DB::transaction(function () use ($configuration, $actor, $force, $health, $override, $observed): void {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $target = BroadcastDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($configuration->id);
            $this->assertActivatable($target);
            $before = $this->settingsState($settings);
            $settings->update(['mode' => BroadcastConfigurationMode::Managed, 'active_configuration_id' => $target->id, 'activated_at' => now(), 'activated_by' => $actor->id]);
            $this->audit->log('broadcast_driver_configurations', $force ? 'force_activate' : 'activate', BroadcastDriverConfiguration::class, $target->id, $before, $this->settingsState($settings->refresh()), ['target_health' => $health->toArray(), 'target_health_override_used' => $override && $force], $actor);
        });

        return $this->result($force, $override, $actor);
    }

    public function activateDeployment(User $actor, bool $force = false): BroadcastActivationResultData
    {
        $observed = $this->state();
        $target = $this->deployment->resolve();
        $health = $this->deployment->test($target);
        $override = $health->status !== BroadcastHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Deployment Broadcast activation preflight failed. '.$health->message]);
        }
        DB::transaction(function () use ($actor, $force, $target, $health, $override, $observed): void {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $this->deployment->resolve();
            $before = $this->settingsState($settings);
            $settings->update(['mode' => BroadcastConfigurationMode::Deployment, 'active_configuration_id' => null, 'activated_at' => now(), 'activated_by' => $actor->id]);
            $this->audit->log('broadcast_driver_configurations', $force ? 'force_activate_deployment' : 'activate_deployment', BroadcastDriverSettings::class, 1, $before, $this->settingsState($settings->refresh()), ['deployment_target' => ['connection' => $target['connection'], 'driver' => $target['driver']], 'target_health' => $health->toArray(), 'target_health_override_used' => $override && $force], $actor);
        });

        return $this->result($force, $override, $actor);
    }

    private function result(bool $force, bool $override, User $actor): BroadcastActivationResultData
    {
        try {
            $this->restart->signal();
            $signaled = true;
        } catch (Throwable) {
            $signaled = false;
        }
        $settings = BroadcastDriverSettings::query()->findOrFail(1);
        $this->audit->log('broadcast_driver_configurations', $signaled ? 'worker_restart_signal_succeeded' : 'worker_restart_signal_failed', BroadcastDriverSettings::class, 1, null, null, ['signal_issued' => $signaled, 'workers_restarted' => false], $actor);

        return new BroadcastActivationResultData($settings, $force, $override && $force, $signaled);
    }

    private function assertActivatable(BroadcastDriverConfiguration $configuration): void
    {
        if ($configuration->trashed() || ! $configuration->is_enabled) {
            throw ValidationException::withMessages(['configuration' => 'Archived or disabled Broadcast configurations cannot be activated.']);
        }
        $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
    }

    private function state(): array
    {
        $settings = BroadcastDriverSettings::query()->find(1);

        return ['mode' => $settings?->mode->value ?? 'deployment', 'active_configuration_id' => $settings?->active_configuration_id];
    }

    private function lockSettings(): BroadcastDriverSettings
    {
        $now = now();
        BroadcastDriverSettings::query()->insertOrIgnore(['id' => 1, 'mode' => 'deployment', 'created_at' => $now, 'updated_at' => $now]);

        return BroadcastDriverSettings::query()->whereKey(1)->lockForUpdate()->firstOrFail();
    }

    private function assertUnchanged(BroadcastDriverSettings $settings, array $observed): void
    {
        if ($settings->mode->value !== $observed['mode'] || $settings->active_configuration_id !== $observed['active_configuration_id']) {
            throw ValidationException::withMessages(['activation' => 'Broadcast ownership changed while activation was prepared.']);
        }
    }

    private function settingsState(BroadcastDriverSettings $settings): array
    {
        return ['mode' => $settings->mode->value, 'active_configuration_id' => $settings->active_configuration_id, 'activated_at' => $settings->activated_at?->toIso8601String(), 'activated_by' => $settings->activated_by];
    }
}
